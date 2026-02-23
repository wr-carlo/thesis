<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentItem;
use App\Models\Student;
use App\Models\StudentAnswer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AssessmentHistoryController extends Controller
{
    /**
     * Display assessment history - list of students who took this assessment.
     */
    public function show(Request $request, Assessment $assessment)
    {
        $professor = auth()->user()->professor;

        if (!$professor) {
            abort(403, 'Instructor record not found');
        }

        $assessment->load(['lesson.subject', 'items']);

        if ($assessment->lesson->professor_id !== $professor->id) {
            abort(403, 'You do not have access to this assessment');
        }

        $attemptsQuery = AssessmentAttempt::where('assessment_id', $assessment->id)
            ->with(['answers', 'student.user', 'student.section']);

        if ($request->filled('search')) {
            $search = $request->search;
            $attemptsQuery->whereHas('student.user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('section')) {
            $attemptsQuery->whereHas('student', function ($q) use ($request) {
                $q->where('section_id', $request->section);
            });
        }

        $attempts = $attemptsQuery->latest('created_at')->get();

        $totalQuestions = $assessment->items->count();

        $attemptsData = $attempts->map(function ($attempt) use ($totalQuestions) {
            $correctAnswers = $attempt->answers->where('correct_answer', true)->count();
            $wrongAnswers = $attempt->answers->where('correct_answer', false)->count();
            $noAnswer = $attempt->answers->whereNull('choices')->count();
            $score = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 2) : 0;

            return [
                'id' => $attempt->id,
                'student_id' => $attempt->student_id,
                'student_name' => $attempt->student->user->name ?? 'Unknown',
                'section_id' => $attempt->student->section_id,
                'section_name' => $attempt->student->section->name ?? null,
                'attempt_no' => $attempt->attempt_no,
                'created_at' => $attempt->created_at,
                'score' => $score,
            ];
        });

        $studentsData = $attemptsData->groupBy('student_id')->map(function ($studentAttempts) {
            $first = $studentAttempts->first();
            return [
                'student_id' => $first['student_id'],
                'student_name' => $first['student_name'],
                'section_id' => $first['section_id'],
                'section_name' => $first['section_name'],
                'attempt_count' => $studentAttempts->count(),
                'best_score' => $studentAttempts->max('score'),
                'latest_attempt_date' => $studentAttempts->first()['created_at'],
            ];
        })->values();

        $sections = AssessmentAttempt::where('assessment_id', $assessment->id)
            ->join('students', 'assessment_attempt.student_id', '=', 'students.id')
            ->join('sections', 'students.section_id', '=', 'sections.id')
            ->select('sections.id', 'sections.name')
            ->distinct()
            ->orderBy('sections.name')
            ->get()
            ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]);

        $totalAttempts = $attemptsData->count();
        $bestScore = $attemptsData->max('score') ?? 0;
        $bestAttempt = $attemptsData->firstWhere('score', $bestScore);
        $latestAttempt = $attemptsData->first();

        $firstAttemptsQuery = AssessmentAttempt::where('assessment_id', $assessment->id)
            ->where('attempt_no', 1);

        if ($request->filled('search')) {
            $search = $request->search;
            $firstAttemptsQuery->whereHas('student.user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('section')) {
            $firstAttemptsQuery->whereHas('student', function ($q) use ($request) {
                $q->where('section_id', $request->section);
            });
        }

        $firstAttemptIds = $firstAttemptsQuery->pluck('id');

        $mistakeCounts = StudentAnswer::whereIn('attempt_id', $firstAttemptIds)
            ->where('correct_answer', false)
            ->selectRaw('assessment_item_id, count(*) as mistake_count')
            ->groupBy('assessment_item_id')
            ->orderByDesc('mistake_count')
            ->get();

        $items = AssessmentItem::whereIn('id', $mistakeCounts->pluck('assessment_item_id'))->get()->keyBy('id');

        $mostCommonMistakes = $mistakeCounts->values()->map(function ($row, $index) use ($items) {
            $item = $items->get($row->assessment_item_id);
            return [
                'item_id' => $row->assessment_item_id,
                'question' => $item ? strip_tags($item->question) : 'Unknown',
                'mistake_count' => (int) $row->mistake_count,
                'rank' => $index + 1,
            ];
        })->values()->all();

        return Inertia::render('Instructor/Assessments/History', [
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
                'total_students' => $studentsData->count(),
                'total_attempts' => $totalAttempts,
                'best_score' => $bestScore,
                'best_student_name' => $bestAttempt['student_name'] ?? null,
                'latest_attempt_date' => $latestAttempt['created_at'] ?? null,
            ],
            'students' => $studentsData,
            'most_common_mistakes' => $mostCommonMistakes,
            'sections' => $sections,
            'filters' => [
                'search' => $request->search ?? null,
                'section' => $request->section ?? null,
            ],
        ]);
    }

    /**
     * Display one student's attempts for this assessment.
     */
    public function showStudent(Assessment $assessment, Student $student)
    {
        $professor = auth()->user()->professor;

        if (!$professor) {
            abort(403, 'Instructor record not found');
        }

        $assessment->load(['lesson.subject', 'items']);

        if ($assessment->lesson->professor_id !== $professor->id) {
            abort(403, 'You do not have access to this assessment');
        }

        $attempts = AssessmentAttempt::where('assessment_id', $assessment->id)
            ->where('student_id', $student->id)
            ->with(['answers', 'student.user'])
            ->orderBy('attempt_no')
            ->get();

        $totalQuestions = $assessment->items->count();

        $adaptivesByAttemptId = Assessment::where('parent_assessment_id', $assessment->id)
            ->whereNotNull('source_attempt_id')
            ->where('type', 'adaptive')
            ->orderBy('created_at')
            ->get()
            ->groupBy('source_attempt_id');

        $attemptsData = $attempts->map(function ($attempt) use ($totalQuestions, $adaptivesByAttemptId) {
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
                'student_name' => $attempt->student->user->name ?? 'Unknown',
                'created_at' => $attempt->created_at,
                'score' => $score,
                'correct_answers' => $correctAnswers,
                'wrong_answers' => $wrongAnswers,
                'no_answer' => $noAnswer,
                'total_questions' => $totalQuestions,
                'adaptive_assessments' => $adaptiveList,
            ];
        });

        $totalAttempts = $attemptsData->count();
        $bestScore = $attemptsData->max('score') ?? 0;
        $bestAttempt = $attemptsData->firstWhere('score', $bestScore);
        $latestAttempt = $attemptsData->last();

        return Inertia::render('Instructor/Assessments/HistoryStudent', [
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
            'student' => [
                'id' => $student->id,
                'name' => $student->user->name ?? 'Unknown',
            ],
            'summary' => [
                'total_attempts' => $totalAttempts,
                'best_score' => $bestScore,
                'best_attempt_id' => $bestAttempt['id'] ?? null,
                'best_attempt_no' => $bestAttempt['attempt_no'] ?? null,
                'latest_attempt_date' => $latestAttempt['created_at'] ?? null,
            ],
            'attempts' => $attemptsData->values(),
        ]);
    }
}
