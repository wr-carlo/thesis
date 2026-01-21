<?php

namespace App\Http\Controllers\Student;

use App\Events\NotificationCreated;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Professor;
use App\Models\StudentSubject;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SubjectController extends Controller
{
    /**
     * Display a listing of all subjects with instructors and student's join status.
     */
    public function index(Request $request)
    {
        $student = auth()->user()->student;

        if (!$student) {
            abort(403, 'Student record not found');
        }

        $student->load('user');

        $search = $request->string('search')->toString();

        // Get all subjects with their assigned professors
        $subjects = Subject::with(['professors.user'])
            ->when($search, function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                });
            })
            ->orderBy('name')
            ->get()
            ->map(function ($subject) use ($student) {
                // Get student's status for this subject
                $studentSubject = StudentSubject::where('student_id', $student->id)
                    ->where('subject_id', $subject->id)
                    ->first();

                // Find which instructor was notified for this request (if any)
                $selectedInstructorId = null;
                if ($studentSubject && $studentSubject->status !== 'not_joined') {
                    $studentName = $student->user->name;
                    $subjectName = $subject->name;
                    $requestTime = $studentSubject->created_at;

                    // Find notification for this request to determine which instructor was selected
                    $notification = Notification::where('description', 'like', "%{$studentName}%")
                        ->where('description', 'like', "%{$subjectName}%")
                        ->whereBetween('created_at', [
                            $requestTime->copy()->subSeconds(10),
                            $requestTime->copy()->addSeconds(10)
                        ])
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if ($notification) {
                        // Get the professor_id from the user_id in notification
                        $professor = \App\Models\Professor::where('user_id', $notification->user_id)->first();
                        if ($professor) {
                            $selectedInstructorId = $professor->id;
                        }
                    }
                }

                // Get instructors assigned to this subject
                $instructors = $subject->professors->map(function ($professor) use ($selectedInstructorId, $studentSubject) {
                    $isSelected = $professor->id === $selectedInstructorId;

                    return [
                        'id' => $professor->id,
                        'name' => $professor->user->name,
                        'user_id' => $professor->user_id,
                        'is_selected' => $isSelected,
                    ];
                });

                return [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'code' => $subject->code,
                    'description' => $subject->description,
                    'instructors' => $instructors,
                    'status' => $studentSubject ? $studentSubject->status : 'not_joined',
                    'student_subject_id' => $studentSubject?->id,
                    'selected_instructor_id' => $selectedInstructorId,
                ];
            });

        return Inertia::render('Student/Subjects/Index', [
            'subjects' => $subjects,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Store a new join request for a subject with a specific instructor.
     */
    public function store(Request $request)
    {
        $student = auth()->user()->student;

        if (!$student) {
            abort(403, 'Student record not found');
        }

        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'professor_id' => 'required|exists:professors,id',
        ]);

        // Verify the professor is assigned to this subject
        $professor = Professor::findOrFail($request->professor_id);
        $subject = Subject::findOrFail($request->subject_id);

        $isAssigned = $professor->subjects()->where('subjects.id', $subject->id)->exists();

        if (!$isAssigned) {
            return back()->withErrors([
                'error' => 'The selected instructor is not assigned to this subject.',
            ]);
        }

        // Check if student already has a record for this subject
        $existingRequest = StudentSubject::where('student_id', $student->id)
            ->where('subject_id', $subject->id)
            ->first();

        if ($existingRequest) {
            // If declined, allow them to request again
            if ($existingRequest->status === 'declined') {
                // Load user relationship if not already loaded
                $student->load('user');

                // Create notification FIRST for the chosen instructor
                $notification = Notification::create([
                    'user_id' => $professor->user_id,
                    'description' => "{$student->user->name} has requested to join {$subject->name}.",
                ]);
                
                // Broadcast the notification
                event(new NotificationCreated($notification));

                // Update status to pending
                // The observer will check if notification exists and skip auto-notifying all instructors
                $existingRequest->status = 'pending';
                $existingRequest->save();

                return back()->with('flash', [
                    'type' => 'success',
                    'message' => 'Join request submitted successfully!',
                ]);
            }

            // If pending or approved, show error
            return back()->withErrors([
                'error' => $existingRequest->status === 'pending'
                    ? 'You already have a pending request for this subject.'
                    : 'You are already enrolled in this subject.',
            ]);
        }

        DB::beginTransaction();

        try {
            // Load user relationship if not already loaded
            $student->load('user');

            // Create notification FIRST for the chosen instructor
            // This ensures the observer knows we're in "manual notification" mode
            Notification::create([
                'user_id' => $professor->user_id,
                'description' => "{$student->user->name} has requested to join {$subject->name}.",
            ]);

            // Create student_subject record
            // The observer will check if notification exists and skip auto-notifying all instructors
            $studentSubject = StudentSubject::create([
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'status' => 'pending',
            ]);

            DB::commit();

            return back()->with('flash', [
                'type' => 'success',
                'message' => 'Join request submitted successfully!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors([
                'error' => 'Failed to submit join request: ' . $e->getMessage(),
            ]);
        }
    }
}
