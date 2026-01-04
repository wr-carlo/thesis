<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'title',
        'type',
        'parent_assessment_id',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_assessment_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_assessment_id');
    }

    public function sections()
    {
        return $this->belongsToMany(Section::class, 'assessment_section');
    }

    public function items()
    {
        return $this->hasMany(AssessmentItem::class);
    }

    public function attempts()
    {
        return $this->hasMany(AssessmentAttempt::class);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}

