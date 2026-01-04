<?php

namespace App\Services\Logging;

use App\Models\Lesson;
use App\Models\LessonProcessingLog;

class ProcessingLogger
{
    /**
     * Log a processing stage.
     *
     * @param Lesson $lesson
     * @param string $stage
     * @param string $status
     * @param string|null $message
     * @param string|null $provider
     * @param array|null $metadata
     * @return LessonProcessingLog
     */
    public function log(
        Lesson $lesson,
        string $stage,
        string $status = 'success',
        ?string $message = null,
        ?string $provider = null,
        ?array $metadata = null
    ): LessonProcessingLog {
        return LessonProcessingLog::create([
            'lesson_id' => $lesson->id,
            'stage' => $stage,
            'status' => $status,
            'message' => $message,
            'provider' => $provider,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log successful stage.
     *
     * @param Lesson $lesson
     * @param string $stage
     * @param string|null $message
     * @param array|null $metadata
     * @return LessonProcessingLog
     */
    public function logSuccess(
        Lesson $lesson,
        string $stage,
        ?string $message = null,
        ?array $metadata = null
    ): LessonProcessingLog {
        return $this->log($lesson, $stage, 'success', $message, null, $metadata);
    }

    /**
     * Log error stage.
     *
     * @param Lesson $lesson
     * @param string $stage
     * @param string $message
     * @param array|null $metadata
     * @return LessonProcessingLog
     */
    public function logError(
        Lesson $lesson,
        string $stage,
        string $message,
        ?array $metadata = null
    ): LessonProcessingLog {
        return $this->log($lesson, $stage, 'error', $message, null, $metadata);
    }

    /**
     * Log AI generation stage.
     *
     * @param Lesson $lesson
     * @param string $provider
     * @param string $status
     * @param string|null $message
     * @param array|null $metadata
     * @return LessonProcessingLog
     */
    public function logAICall(
        Lesson $lesson,
        string $provider,
        string $status = 'success',
        ?string $message = null,
        ?array $metadata = null
    ): LessonProcessingLog {
        return $this->log($lesson, 'ai_call', $status, $message, $provider, $metadata);
    }
}

