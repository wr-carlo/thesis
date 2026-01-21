<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\SubmitAssessmentRequest;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentItem;
use App\Models\StudentAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AssessmentController extends Controller
{
    /**
     * Display a listing of available assessments.
     */
    public function index(Request $request)
    {
        $student = auth()->user()->student;

        if (!$student) {
            abort(403, 'Student record not found');
        }

        $search = $request->string('search')->toString();

        // Get accessible assessments
        $assessments = Assessment::accessibleBy($student)
            ->with(['lesson.subject', 'items'])
            ->when($search, function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('title', 'like', "%{$term}%")
                        ->orWhereHas('lesson', function ($lessonQuery) use ($term) {
                            $lessonQuery->where('title', 'like', "%{$term}%")
                                ->orWhereHas('subject', function ($subjectQuery) use ($term) {
                                    $subjectQuery->where('name', 'like', "%{$term}%")
                                        ->orWhere('code', 'like', "%{$term}%");
                                });
                        });
                });
            })
            ->latest()
            ->get()
            ->map(function ($assessment) use ($student) {
                // Get attempt count for this student
                $attemptCount = AssessmentAttempt::where('student_id', $student->id)
                    ->where('assessment_id', $assessment->id)
                    ->count();

                // Get latest attempt
                $latestAttempt = AssessmentAttempt::where('student_id', $student->id)
                    ->where('assessment_id', $assessment->id)
                    ->latest('created_at')
                    ->first();

                return [
                    'id' => $assessment->id,
                    'title' => $assessment->title,
                    'lesson' => [
                        'id' => $assessment->lesson->id,
                        'title' => $assessment->lesson->title,
                    ],
                    'subject' => [
                        'id' => $assessment->lesson->subject->id,
                        'name' => $assessment->lesson->subject->name,
                        'code' => $assessment->lesson->subject->code,
                    ],
                    'item_count' => $assessment->items->count(),
                    'attempt_count' => $attemptCount,
                    'last_attempt_at' => $latestAttempt?->created_at,
                ];
            });

        return Inertia::render('Student/Assessments/Index', [
            'assessments' => $assessments,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Display the assessment for taking.
     */
    public function show(Assessment $assessment)
    {
        $student = auth()->user()->student;

        if (!$student) {
            abort(403, 'Student record not found');
        }

        // Check if student can access this assessment
        if (!$assessment->canBeAccessedBy($student)) {
            abort(403, 'You do not have access to this assessment');
        }

        // Load assessment with items
        $assessment->load(['lesson.subject', 'items']);

        // Get items formatted for display
        $items = $assessment->items->map(function ($item) {
            return [
                'id' => $item->id,
                'question' => $item->question,
                'type' => $item->type,
                'choices' => $item->type === 'multiple_choice' && is_array($item->choices)
                    ? $item->choices
                    : ($item->type === 'multiple_choice' && $item->choices
                        ? json_decode($item->choices, true) ?? []
                        : null),
            ];
        });

        return Inertia::render('Student/Assessments/Take', [
            'assessment' => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'lesson' => [
                    'title' => $assessment->lesson->title,
                ],
                'subject' => [
                    'name' => $assessment->lesson->subject->name,
                    'code' => $assessment->lesson->subject->code,
                ],
            ],
            'items' => $items,
        ]);
    }

    /**
     * Store the assessment attempt and answers.
     */
    public function store(SubmitAssessmentRequest $request, Assessment $assessment)
    {
        $student = auth()->user()->student;

        if (!$student) {
            abort(403, 'Student record not found');
        }

        // Check if student can access this assessment
        if (!$assessment->canBeAccessedBy($student)) {
            abort(403, 'You do not have access to this assessment');
        }

        // Load assessment items for comparison
        $assessment->load('items');

        DB::beginTransaction();

        try {
            // Get next attempt number
            $attemptNo = AssessmentAttempt::getNextAttemptNumber($student->id, $assessment->id);

            // Create assessment attempt
            $attempt = AssessmentAttempt::create([
                'student_id' => $student->id,
                'assessment_id' => $assessment->id,
                'attempt_no' => $attemptNo,
            ]);

            // Process each answer
            $answers = $request->validated()['answers'];

            foreach ($answers as $itemId => $studentAnswerData) {
                $item = $assessment->items->firstWhere('id', $itemId);

                if (!$item) {
                    continue; // Skip if item not found
                }

                $studentAnswer = $studentAnswerData['answer'] ?? null;

                if ($studentAnswer === null || $studentAnswer === '') {
                    continue; // Skip empty answers
                }

                // Format answer based on type
                $formattedAnswer = $this->formatAnswer($item->type, $studentAnswer);

                // Compare with correct answer
                $isCorrect = $this->compareAnswer(
                    $item->type,
                    $studentAnswer,
                    $item->correct_answer
                );

                // Create student answer record
                StudentAnswer::create([
                    'attempt_id' => $attempt->id,
                    'assessment_item_id' => $itemId,
                    'type' => $item->type,
                    'choices' => $formattedAnswer,
                    'correct_answer' => $isCorrect,
                ]);
            }

            DB::commit();

            return redirect()->route('student.assessments.results', [
                'assessment' => $assessment->id,
                'attempt' => $attempt->id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors([
                'error' => 'Failed to submit assessment: ' . $e->getMessage(),
            ])->withInput();
        }
    }

    /**
     * Display assessment attempt history.
     */
    public function history(Assessment $assessment)
    {
        $student = auth()->user()->student;

        if (!$student) {
            abort(403, 'Student record not found');
        }

        // Verify student can access this assessment
        if (!$assessment->canBeAccessedBy($student)) {
            abort(403, 'You do not have access to this assessment');
        }

        // Load assessment with related data
        $assessment->load(['lesson.subject', 'items']);

        // Get all attempts for this student and assessment
        $attempts = AssessmentAttempt::where('student_id', $student->id)
            ->where('assessment_id', $assessment->id)
            ->with('answers')
            ->latest('created_at')
            ->get();

        $totalQuestions = $assessment->items->count();

        // Process each attempt to calculate scores and stats
        $attemptsData = $attempts->map(function ($attempt) use ($totalQuestions) {
            $answeredQuestions = $attempt->answers->count();
            $correctAnswers = $attempt->answers->where('correct_answer', true)->count();
            $wrongAnswers = $answeredQuestions - $correctAnswers;
            $noAnswer = $totalQuestions - $answeredQuestions;
            $score = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 2) : 0;

            return [
                'id' => $attempt->id,
                'attempt_no' => $attempt->attempt_no,
                'created_at' => $attempt->created_at,
                'score' => $score,
                'correct_answers' => $correctAnswers,
                'wrong_answers' => $wrongAnswers,
                'no_answer' => $noAnswer,
                'total_questions' => $totalQuestions,
            ];
        });

        // Calculate summary statistics
        $totalAttempts = $attemptsData->count();
        $bestScore = $attemptsData->max('score') ?? 0;
        $bestAttempt = $attemptsData->firstWhere('score', $bestScore);
        $latestAttempt = $attemptsData->first();

        return Inertia::render('Student/Assessments/History', [
            'assessment' => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'lesson' => [
                    'title' => $assessment->lesson->title,
                ],
                'subject' => [
                    'name' => $assessment->lesson->subject->name,
                    'code' => $assessment->lesson->subject->code,
                ],
            ],
            'summary' => [
                'total_attempts' => $totalAttempts,
                'best_score' => $bestScore,
                'best_attempt_no' => $bestAttempt['attempt_no'] ?? null,
                'latest_attempt_date' => $latestAttempt['created_at'] ?? null,
            ],
            'attempts' => $attemptsData->values(),
        ]);
    }

    /**
     * Display assessment results.
     */
    public function results(Assessment $assessment, AssessmentAttempt $attempt)
    {
        $student = auth()->user()->student;

        if (!$student) {
            abort(403, 'Student record not found');
        }

        // Verify attempt belongs to this student and assessment
        if ($attempt->student_id !== $student->id || $attempt->assessment_id !== $assessment->id) {
            abort(403, 'Unauthorized access');
        }

        // Load assessment with items
        $assessment->load(['items', 'lesson.subject']);

        // Load attempt with answers
        $attempt->load('answers');

        // Prepare results data
        $totalQuestions = $assessment->items->count();
        $answeredQuestions = $attempt->answers->count();
        $correctAnswers = $attempt->answers->where('correct_answer', true)->count();
        $wrongAnswers = $answeredQuestions - $correctAnswers;
        $noAnswer = $totalQuestions - $answeredQuestions;
        $score = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 2) : 0;

        // Prepare items with student answers
        $items = $assessment->items->map(function ($item) use ($attempt) {
            $studentAnswer = $attempt->answers->firstWhere('assessment_item_id', $item->id);

            $studentAnswerText = null;
            if ($studentAnswer && $studentAnswer->choices) {
                $choices = $studentAnswer->choices;
                $studentAnswerText = is_array($choices) ? ($choices[0] ?? '') : '';
            }

            return [
                'id' => $item->id,
                'question' => $item->question,
                'type' => $item->type,
                'choices' => $item->choices,
                'correct_answer' => $item->correct_answer,
                'student_answer' => $studentAnswerText,
                'is_correct' => $studentAnswer ? $studentAnswer->correct_answer : false,
            ];
        });

        return Inertia::render('Student/Assessments/Results', [
            'assessment' => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'lesson' => [
                    'title' => $assessment->lesson->title,
                ],
                'subject' => [
                    'name' => $assessment->lesson->subject->name,
                    'code' => $assessment->lesson->subject->code,
                ],
            ],
            'attempt' => [
                'id' => $attempt->id,
                'attempt_no' => $attempt->attempt_no,
                'created_at' => $attempt->created_at,
            ],
            'results' => [
                'total_questions' => $totalQuestions,
                'answered_questions' => $answeredQuestions,
                'correct_answers' => $correctAnswers,
                'wrong_answers' => $wrongAnswers,
                'no_answer' => $noAnswer,
                'score' => $score,
            ],
            'items' => $items,
        ]);
    }

    /**
     * Format student answer based on question type.
     *
     * @param string $type
     * @param mixed $answer
     * @return array|null
     */
    protected function formatAnswer(string $type, $answer): ?array
    {
        return match ($type) {
            'multiple_choice' => is_array($answer) ? $answer : [$answer],
            'identification' => [$answer],
            'true_or_false' => [ucfirst(strtolower($answer))],
            default => [$answer],
        };
    }

    /**
     * Compare student answer with correct answer.
     *
     * @param string $type
     * @param mixed $studentAnswer
     * @param string|null $correctAnswer
     * @return bool
     */
    protected function compareAnswer(string $type, $studentAnswer, ?string $correctAnswer): bool
    {
        if ($correctAnswer === null) {
            return false;
        }

        return match ($type) {
            'multiple_choice' => trim($studentAnswer) === trim($correctAnswer),
            'identification' => strtolower(trim($studentAnswer)) === strtolower(trim($correctAnswer)),
            'true_or_false' => strtolower(trim($studentAnswer)) === strtolower(trim($correctAnswer)),
            default => false,
        };
    }
}
