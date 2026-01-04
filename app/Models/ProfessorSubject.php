<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfessorSubject extends Model
{
    use HasFactory;

    protected $table = 'professor_subject';

    protected $fillable = [
        'professor_id',
        'subject_id',
    ];

    public function professor()
    {
        return $this->belongsTo(Professor::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}

