<?php

namespace App\Observers;

use App\Events\NotificationCreated;
use App\Models\Notification;
use App\Models\StudentSubject;
use App\Models\Subject;

class StudentSubjectObserver
{
    /**
     * Handle the StudentSubject "created" event.
     */
    public function created(StudentSubject $studentSubject): void
    {
        // Only notify if status is 'pending' (new join request)
        if ($studentSubject->status === 'pending') {
            $studentSubject->load(['student.user', 'subject.professors.user']);
            
            // Check if a notification already exists for this request
            // This happens when a student specifically chooses an instructor
            // If a notification exists, it means we're in "manual notification" mode
            // and we should skip the automatic notification to all instructors
            $studentName = $studentSubject->student->user->name;
            $subjectName = $studentSubject->subject->name;
            $hasExistingNotification = Notification::where('description', 'like', "%{$studentName}%")
                ->where('description', 'like', "%{$subjectName}%")
                ->whereBetween('created_at', [
                    $studentSubject->created_at->copy()->subSeconds(5),
                    $studentSubject->created_at->copy()->addSeconds(5)
                ])
                ->exists();
            
            // Only auto-notify all instructors if no manual notification was created
            // This allows the student join flow to notify only the chosen instructor
            if (!$hasExistingNotification) {
                // Notify all instructors assigned to this subject (legacy behavior)
                foreach ($studentSubject->subject->professors as $professor) {
                    $notification = Notification::create([
                        'user_id' => $professor->user_id,
                        'description' => "{$studentSubject->student->user->name} has requested to join {$studentSubject->subject->name}.",
                    ]);
                    
                    // Broadcast the notification
                    event(new NotificationCreated($notification));
                }
            }
        }
    }

    /**
     * Handle the StudentSubject "updated" event.
     */
    public function updated(StudentSubject $studentSubject): void
    {
        // Notifications for approve/decline are handled in SubjectController
        // This observer is mainly for new join requests
    }
}
