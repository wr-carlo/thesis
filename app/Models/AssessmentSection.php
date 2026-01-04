<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentSection extends Model
{
    use HasFactory;

    protected $table = 'assessment_section';

    protected $fillable = [
        'assessment_id',
        'section_id',
    ];

    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}

