<?php

namespace App\Observers;

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
            
            // Notify all instructors assigned to this subject
            foreach ($studentSubject->subject->professors as $professor) {
                Notification::create([
                    'user_id' => $professor->user_id,
                    'description' => "{$studentSubject->student->user->name} has requested to join {$studentSubject->subject->name}.",
                ]);
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
