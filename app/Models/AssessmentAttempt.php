<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentAttempt extends Model
{
    use HasFactory;

    protected $table = 'assessment_attempt';

    protected $fillable = [
        'student_id',
        'assessment_id',
        'attempt_no',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }

    public function answers()
    {
        return $this->hasMany(StudentAnswer::class, 'attempt_id');
    }

    /**
     * Get the next attempt number for a student and assessment.
     *
     * @param int $studentId
     * @param int $assessmentId
     * @return int
     */
    public static function getNextAttemptNumber(int $studentId, int $assessmentId): int
    {
        $maxAttempt = self::where('student_id', $studentId)
            ->where('assessment_id', $assessmentId)
            ->max('attempt_no');

        return $maxAttempt ? $maxAttempt + 1 : 1;
    }
}

