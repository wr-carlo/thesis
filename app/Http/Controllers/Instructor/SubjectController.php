<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Log;
use App\Models\Notification;
use App\Models\StudentSubject;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubjectController extends Controller
{
    /**
     * Display a listing of assigned subjects with pending request counts.
     */
    public function index()
    {
        $professor = auth()->user()->professor;

        $subjects = $professor->subjects()
            ->selectRaw('subjects.*, (
                SELECT COUNT(*) 
                FROM student_subject 
                WHERE student_subject.subject_id = subjects.id 
                AND student_subject.status = "pending"
            ) as pending_requests_count')
            ->orderBy('name')
            ->get();

        return Inertia::render('Instructor/Subjects/Index', [
            'subjects' => $subjects,
        ]);
    }

    /**
     * Display join requests for a specific subject.
     */
    public function requests(Subject $subject)
    {
        // Verify instructor is assigned to this subject
        $professor = auth()->user()->professor;
        
        $subject->load('professors');
        if (!$subject->professors->contains($professor->id)) {
            abort(403, 'Unauthorized access to this subject');
        }

        // Get pending join requests
        $requests = StudentSubject::where('subject_id', $subject->id)
            ->where('status', 'pending')
            ->with(['student.user', 'student.section'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'student_name' => $request->student->user->name,
                    'student_id_number' => $request->student->user->id_number,
                    'section_name' => $request->student->section->name,
                    'requested_at' => $request->created_at,//->format('M d, Y h:i A'),
                    'created_at' => $request->created_at,
                ];
            });

        return Inertia::render('Instructor/Subjects/Requests', [
            'subject' => $subject,
            'requests' => $requests,
        ]);
    }

    /**
     * Approve a join request.
     */
    public function approve(Subject $subject, StudentSubject $studentSubject)
    {
        // Verify instructor is assigned to this subject
        $professor = auth()->user()->professor;
        
        $subject->load('professors');
        if (!$subject->professors->contains($professor->id)) {
            abort(403, 'Unauthorized access to this subject');
        }

        // Verify the request belongs to this subject
        if ($studentSubject->subject_id !== $subject->id) {
            abort(404, 'Request not found for this subject');
        }

        // Eager load student and user relationships
        $studentSubject->load(['student.user']);

        // Update status
        $studentSubject->status = 'approved';
        $studentSubject->save();

        // Create notification for student
        Notification::create([
            'user_id' => $studentSubject->student->user_id,
            'description' => "Your request to join {$subject->name} has been approved.",
        ]);

        // Log the action
        $this->logAction(
            auth()->user(),
            "Approved join request for {$studentSubject->student->user->name} to {$subject->name}"
        );

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Join request approved successfully.',
        ]);
    }

    /**
     * Decline a join request.
     */
    public function decline(Subject $subject, StudentSubject $studentSubject)
    {
        // Verify instructor is assigned to this subject
        $professor = auth()->user()->professor;
        
        $subject->load('professors');
        if (!$subject->professors->contains($professor->id)) {
            abort(403, 'Unauthorized access to this subject');
        }

        // Verify the request belongs to this subject
        if ($studentSubject->subject_id !== $subject->id) {
            abort(404, 'Request not found for this subject');
        }

        // Eager load student and user relationships
        $studentSubject->load(['student.user']);

        // Update status
        $studentSubject->status = 'declined';
        $studentSubject->save();

        // Create notification for student
        Notification::create([
            'user_id' => $studentSubject->student->user_id,
            'description' => "Your request to join {$subject->name} has been declined.",
        ]);

        // Log the action
        $this->logAction(
            auth()->user(),
            "Declined join request for {$studentSubject->student->user->name} to {$subject->name}"
        );

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Join request declined.',
        ]);
    }

    /**
     * Log instructor action.
     */
    private function logAction(User $actor, string $description): void
    {
        Log::create([
            'user_id' => $actor->id,
            'description' => $description,
            'role' => $actor->role,
        ]);
    }
}

