<?php

namespace App\Http\Controllers\Instructor;

use App\Events\NotificationCreated;
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
    public function index(Request $request)
    {
        $professor = auth()->user()->professor;
        $search = $request->string('search')->toString();

        $subjects = $professor->subjects()
            ->selectRaw('subjects.*, (
                SELECT COUNT(*) 
                FROM student_subject 
                WHERE student_subject.subject_id = subjects.id 
                AND student_subject.status = "pending"
            ) as pending_requests_count, (
                SELECT COUNT(*) 
                FROM student_subject 
                WHERE student_subject.subject_id = subjects.id 
                AND student_subject.status = "approved"
            ) as enrolled_students_count')
            ->when($search, function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('subjects.name', 'like', "%{$term}%")
                        ->orWhere('subjects.code', 'like', "%{$term}%")
                        ->orWhere('subjects.description', 'like', "%{$term}%");
                });
            })
            ->orderBy('name')
            ->get();

        return Inertia::render('Instructor/Subjects/Index', [
            'subjects' => $subjects,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Display join requests for a specific subject.
     * Only shows requests where this instructor was specifically chosen.
     */
    public function requests(Subject $subject)
    {
        // Verify instructor is assigned to this subject
        $professor = auth()->user()->professor;
        
        $subject->load('professors');
        if (!$subject->professors->contains($professor->id)) {
            abort(403, 'Unauthorized access to this subject');
        }

        // Get pending join requests for this subject
        $allRequests = StudentSubject::where('subject_id', $subject->id)
            ->where('status', 'pending')
            ->with(['student.user', 'student.section'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Filter requests where this instructor has a notification
        // This ensures instructors only see requests where they were specifically chosen
        // We match by checking if a notification exists for this instructor
        // that was created around the same time as the student_subject record
        $requests = $allRequests->filter(function ($request) use ($professor, $subject) {
            // Check if there's a notification for this instructor about this request
            // Match by student name, subject name, and created time (within 5 seconds)
            $studentName = $request->student->user->name;
            $subjectName = $subject->name;
            $requestTime = $request->created_at;
            
            return Notification::where('user_id', $professor->user_id)
                ->where('description', 'like', "%{$studentName}%")
                ->where('description', 'like', "%{$subjectName}%")
                ->whereBetween('created_at', [
                    $requestTime->copy()->subSeconds(5),
                    $requestTime->copy()->addSeconds(5)
                ])
                ->exists();
        })->map(function ($request) {
            return [
                'id' => $request->id,
                'student_name' => $request->student->user->name,
                'student_id_number' => $request->student->user->id_number,
                'section_name' => $request->student->section->name,
                'requested_at' => $request->created_at,//->format('M d, Y h:i A'),
                'created_at' => $request->created_at,
            ];
        })->values();

        // Get approved students for this subject
        $approvedStudents = StudentSubject::where('subject_id', $subject->id)
            ->where('status', 'approved')
            ->with(['student.user', 'student.section'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($record) {
                return [
                    'id' => $record->id,
                    'student_name' => $record->student->user->name,
                    'student_id_number' => $record->student->user->id_number,
                    'section_name' => $record->student->section->name,
                    'approved_at' => $record->updated_at,
                ];
            });

        return Inertia::render('Instructor/Subjects/Requests', [
            'subject' => $subject,
            'requests' => $requests,
            'approvedStudents' => $approvedStudents,
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
        $notification = Notification::create([
            'user_id' => $studentSubject->student->user_id,
            'description' => "Your request to join {$subject->name} has been approved.",
        ]);
        
        // Broadcast the notification
        event(new NotificationCreated($notification));

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
        $notification = Notification::create([
            'user_id' => $studentSubject->student->user_id,
            'description' => "Your request to join {$subject->name} has been declined.",
        ]);
        
        // Broadcast the notification
        event(new NotificationCreated($notification));

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
     * Drop a joined student.
     */
    public function drop(Subject $subject, StudentSubject $studentSubject)
    {
        // Verify instructor is assigned to this subject
        $professor = auth()->user()->professor;
        
        $subject->load('professors');
        if (!$subject->professors->contains($professor->id)) {
            abort(403, 'Unauthorized access to this subject');
        }

        // Verify the request belongs to this subject
        if ($studentSubject->subject_id !== $subject->id) {
            abort(404, 'Student not found in this subject');
        }

        // Eager load student and user relationships
        $studentSubject->load(['student.user']);

        // Update status and delete so they can rejoin later if needed or stay tracked. Let's delete the record.
        // Or set status to dropped. Deleting allows them to request again. Let's just delete the record entirely.
        $studentName = $studentSubject->student->user->name;
        $studentId = $studentSubject->student->user_id;

        $studentSubject->delete();

        // Create notification for student
        $notification = Notification::create([
            'user_id' => $studentId,
            'description' => "You have been dropped from {$subject->name}.",
        ]);
        
        // Broadcast the notification
        event(new NotificationCreated($notification));

        // Log the action
        $this->logAction(
            auth()->user(),
            "Dropped student {$studentName} from {$subject->name}"
        );

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Student dropped successfully.',
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

