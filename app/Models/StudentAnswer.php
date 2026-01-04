<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAnswer extends Model
{
    use HasFactory;

    protected $table = 'student_answer';

    protected $fillable = [
        'attempt_id',
        'assessment_item_id',
        'type',
        'choices',
        'correct_answer',
    ];

    protected $casts = [
        'choices' => 'array',
    ];

    public function attempt()
    {
        return $this->belongsTo(AssessmentAttempt::class, 'attempt_id');
    }

    public function item()
    {
        return $this->belongsTo(AssessmentItem::class, 'assessment_item_id');
    }
}

