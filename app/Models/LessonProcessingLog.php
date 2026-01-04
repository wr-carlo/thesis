<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonProcessingLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'stage',
        'status',
        'message',
        'provider',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}
