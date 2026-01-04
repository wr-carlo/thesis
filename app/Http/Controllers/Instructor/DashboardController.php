<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\StudentSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $professor = auth()->user()->professor;

        if (!$professor) {
            abort(403, 'Professor profile not found');
        }

        // Get professor's subject IDs
        $subjectIds = $professor->subjects()->pluck('subjects.id');

        // Calculate statistics (limited to available features)
        $stats = [
            'total_subjects' => $professor->subjects()->count(),
            'total_students' => StudentSubject::whereIn('subject_id', $subjectIds)
                ->where('status', 'approved')
                ->distinct('student_id')
                ->count('student_id'),
            'pending_requests' => StudentSubject::whereIn('subject_id', $subjectIds)
                ->where('status', 'pending')
                ->count(),
        ];

        return Inertia::render('Instructor/Dashboard', [
            'stats' => $stats,
        ]);
    }
}

