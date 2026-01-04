<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
    ];

    public function professors()
    {
        return $this->belongsToMany(Professor::class, 'professor_subject');
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_subject')->withPivot('status');
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }
}

