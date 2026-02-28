<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\SubmitAssessmentRequest;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentItem;
use App\Models\Notification;
use App\Models\StudentAnswer;
use App\Services\AI\AIServiceManager;
use App\Services\AI\AIResponseParser;
use App\Services\Assessment\AssessmentGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AssessmentController extends Controller
{
    public function __construct(
        protected AIServiceManager $aiManager,
        protected AIResponseParser $aiParser,
        protected AssessmentGenerator $assessmentGenerator
    ) {
    }
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

        // Get accessible assessments (exclude adaptive; those are shown in History accordion)
        $assessments = Assessment::accessibleBy($student)
            ->where('type', '!=', 'adaptive')
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

            // Process all assessment items to ensure empty answers are also stored
            foreach ($assessment->items as $item) {
                $itemId = $item->id;
                $studentAnswerData = $answers[$itemId] ?? null;
                $studentAnswer = $studentAnswerData['answer'] ?? null;

                // Check if answer is empty or null
                $isEmpty = ($studentAnswer === null || $studentAnswer === '');

                // Format answer based on type (handle empty answers)
                if ($isEmpty) {
                    $formattedAnswer = null; // Store null for empty answers
                    $isCorrect = false; // Empty answers are considered wrong
                } else {
                    $formattedAnswer = $this->formatAnswer($item->type, $studentAnswer);
                    
                    // Compare with correct answer
                    $isCorrect = $this->compareAnswer(
                        $item->type,
                        $studentAnswer,
                        $item->correct_answer
                    );
                }

                // Create student answer record (even for empty answers)
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

        // Load adaptive assessments generated from this assessment, grouped by source attempt
        $adaptivesByAttemptId = Assessment::where('parent_assessment_id', $assessment->id)
            ->whereNotNull('source_attempt_id')
            ->where('type', 'adaptive')
            ->orderBy('created_at')
            ->get()
            ->groupBy('source_attempt_id');

        // Process each attempt to calculate scores and stats
        $attemptsData = $attempts->map(function ($attempt) use ($totalQuestions, $adaptivesByAttemptId) {
            // Count answers with actual values (not null choices)
            $answeredQuestions = $attempt->answers->whereNotNull('choices')->count();
            $correctAnswers = $attempt->answers->where('correct_answer', true)->count();
            $wrongAnswers = $attempt->answers->where('correct_answer', false)->count();
            $noAnswer = $attempt->answers->whereNull('choices')->count();
            $score = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 2) : 0;

            $adaptiveList = $adaptivesByAttemptId->get($attempt->id, collect())->map(function ($adaptive) {
                return [
                    'id' => $adaptive->id,
                    'title' => $adaptive->title,
                    'created_at' => $adaptive->created_at,
                ];
            })->values()->all();

            return [
                'id' => $attempt->id,
                'attempt_no' => $attempt->attempt_no,
                'created_at' => $attempt->created_at,
                'score' => $score,
                'correct_answers' => $correctAnswers,
                'wrong_answers' => $wrongAnswers,
                'no_answer' => $noAnswer,
                'total_questions' => $totalQuestions,
                'adaptive_assessments' => $adaptiveList,
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
        // Count answers with actual values (not null choices)
        $answeredQuestions = $attempt->answers->whereNotNull('choices')->count();
        $correctAnswers = $attempt->answers->where('correct_answer', true)->count();
        $wrongAnswers = $attempt->answers->where('correct_answer', false)->count();
        $noAnswer = $attempt->answers->whereNull('choices')->count();
        $score = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 2) : 0;

        // Prepare items with student answers
        $items = $assessment->items->map(function ($item) use ($attempt) {
            $studentAnswer = $attempt->answers->firstWhere('assessment_item_id', $item->id);

            $studentAnswerText = null;
            // Check if answer exists and has non-empty choices
            if ($studentAnswer && $studentAnswer->choices && !empty($studentAnswer->choices)) {
                $choices = $studentAnswer->choices;
                $studentAnswerText = is_array($choices) ? ($choices[0] ?? '') : '';
            }

            return [
                'id' => $item->id,
                'question' => $item->question,
                'type' => $item->type,
                'choices' => $item->choices,
                'correct_answer' => $item->correct_answer,
                'student_answer' => $studentAnswerText, // null for empty answers
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
            'show_adaptive_button' => $score < 100,
            'has_wrong_answers' => $wrongAnswers > 0,
            'items' => $items,
        ]);
    }

    /**
     * Generate adaptive assessment based on wrong answers and redirect to take it.
     */
    public function generateAdaptive(Assessment $assessment, AssessmentAttempt $attempt)
    {
        $student = auth()->user()->student;

        if (!$student) {
            abort(403, 'Student record not found');
        }

        if ($attempt->student_id !== $student->id || $attempt->assessment_id !== $assessment->id) {
            abort(403, 'Unauthorized access');
        }

        $attempt->load(['answers' => function ($q) {
            $q->where('correct_answer', false);
        }, 'answers.item']);

        $wrongAnswers = $attempt->answers;

        if ($wrongAnswers->isEmpty()) {
            return back()->withErrors(['error' => 'No wrong answers to generate adaptive assessment from.']);
        }

        $totalQuestions = $assessment->items->count();
        $correctCount = $attempt->answers()->where('correct_answer', true)->count();
        $score = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100, 2) : 0;

        if ($score >= 100) {
            return back()->withErrors(['error' => 'You have a perfect score. No adaptive assessment needed.']);
        }

        $assessment->load(['lesson', 'sections']);
        $lesson = $assessment->lesson;

        if (empty($lesson->extracted_content)) {
            return back()->withErrors(['error' => 'Lesson content is not available for adaptive assessment generation.']);
        }

        $countByType = [
            'multiple_choice' => 0,
            'identification' => 0,
            'true_or_false' => 0,
        ];

        foreach ($wrongAnswers as $answer) {
            if (isset($countByType[$answer->type])) {
                $countByType[$answer->type]++;
            }
        }

        $multipleChoiceCount = $countByType['multiple_choice'] * 2;
        $identificationCount = $countByType['identification'] * 2;
        $trueOrFalseCount = $countByType['true_or_false'] * 2;

        if ($multipleChoiceCount + $identificationCount + $trueOrFalseCount === 0) {
            return back()->withErrors(['error' => 'Unable to determine question types for adaptive assessment.']);
        }

        $wrongAnswersText = "STUDENT'S WRONG ANSWERS (use these learning gaps to generate new practice questions):\n\n";
        foreach ($wrongAnswers as $idx => $answer) {
            $item = $answer->item;
            if (!$item) {
                continue;
            }
            $studentAnswerText = $answer->choices && is_array($answer->choices)
                ? ($answer->choices[0] ?? '(no answer)')
                : '(no answer)';
            $wrongAnswersText .= ($idx + 1) . ". Question: {$item->question}\n";
            $wrongAnswersText .= "   Student Answer: {$studentAnswerText}\n";
            $wrongAnswersText .= "   Correct Answer: " . ($item->correct_answer ?? '') . "\n";
            $wrongAnswersText .= "   Type: {$answer->type}\n\n";
        }

        $content = $wrongAnswersText . "\n\nLESSON CONTENT (use this to generate questions that address the learning gaps above):\n\n" . $lesson->extracted_content;

        $config = [
            'multiple_choice_count' => $multipleChoiceCount,
            'identification_count' => $identificationCount,
            'true_or_false_count' => $trueOrFalseCount,
            'difficulty' => 'medium',
        ];

        try {
            $aiResult = $this->aiManager->generateAssessment($content, $config);
        } catch (\Exception $e) {
            Log::error('Adaptive assessment AI generation failed', [
                'assessment_id' => $assessment->id,
                'attempt_id' => $attempt->id,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors(['error' => 'Failed to generate adaptive questions. Please try again.']);
        }

        $rawData = $aiResult['data'] ?? [];
        $parsed = [
            'multiple_choice' => $rawData['multiple_choice'] ?? [],
            'identification' => $rawData['identification'] ?? [],
            'true_or_false' => $rawData['true_or_false'] ?? [],
        ];

        try {
            $this->aiParser->parse($parsed);
        } catch (\Exception $e) {
            Log::error('Adaptive assessment parse failed', [
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors(['error' => 'Invalid response from question generator. Please try again.']);
        }

        DB::beginTransaction();
        try {
            $adaptiveAssessment = $this->assessmentGenerator->generate($lesson, $parsed, [
                'title' => "Adaptive Assessment for {$assessment->title}",
                'type' => 'adaptive',
            ]);

            $adaptiveAssessment->update([
                'parent_assessment_id' => $assessment->id,
                'source_attempt_id' => $attempt->id,
                'status' => 'published',
            ]);

            $sectionIds = $assessment->sections->pluck('id')->toArray();
            if (!empty($sectionIds)) {
                $adaptiveAssessment->sections()->sync($sectionIds);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Adaptive assessment save failed', [
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors(['error' => 'Failed to save adaptive assessment. Please try again.']);
        }

        return redirect()->route('student.assessments.show', $adaptiveAssessment)
            ->with('success', 'Adaptive assessment generated. You can take it now.');
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

    /**
     * Log cheating activity (tab switch, page leave, window blur) and notify the instructor.
     */
    public function logCheating(Request $request, Assessment $assessment)
    {
        $student = auth()->user()->student;

        if (!$student) {
            abort(403, 'Student record not found');
        }

        $request->validate([
            'event_type' => 'required|in:tab_switch,page_leave,window_blur',
        ]);

        $eventType = $request->input('event_type');

        $eventLabels = [
            'tab_switch' => 'switched tabs or minimized the window',
            'page_leave' => 'attempted to leave the page',
            'window_blur' => 'switched to another window',
        ];

        $eventLabel = $eventLabels[$eventType] ?? $eventType;
        $studentName = auth()->user()->name;
        $assessmentTitle = $assessment->title;

        // Find the instructor's user_id via assessment → lesson → professor → user
        $assessment->load('lesson.professor');
        $instructorUserId = $assessment->lesson->professor->user_id ?? null;

        if ($instructorUserId) {
            Notification::create([
                'user_id' => $instructorUserId,
                'description' => "⚠️ Cheating Alert: {$studentName} {$eventLabel} while taking \"{$assessmentTitle}\".",
            ]);
        }

        return response()->json(['success' => true]);
    }
}
