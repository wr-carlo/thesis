<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_id',
        'question',
        'type',
        'choices',
        'correct_answer',
    ];

    protected $casts = [
        'choices' => 'array',
    ];

    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }

    public function answers()
    {
        return $this->hasMany(StudentAnswer::class, 'assessment_item_id');
    }
}

