<?php

namespace App\Observers;

use App\Models\AssessmentAttempt;
use App\Models\Notification;

class AssessmentAttemptObserver
{
    /**
     * Handle the AssessmentAttempt "created" event.
     */
    public function created(AssessmentAttempt $assessmentAttempt): void
    {
        // Notify instructor when student completes/submits assessment
        $assessmentAttempt->load(['assessment.lesson.professor.user', 'student.user']);
        
        if ($assessmentAttempt->assessment->lesson->professor) {
            Notification::create([
                'user_id' => $assessmentAttempt->assessment->lesson->professor->user_id,
                'description' => "{$assessmentAttempt->student->user->name} completed {$assessmentAttempt->assessment->title}.",
            ]);
        }
    }
}
