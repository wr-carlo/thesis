<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Log;
use App\Models\Professor;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'professors' => Professor::count(),
            'students' => User::where('role', 'student')->count(),
            'departments' => Department::count(),
            'sections' => Section::count(),
            'subjects' => Subject::count(),
        ];

        $logs = Log::with('user')->latest()->take(5)->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'logs' => $logs,
        ]);
    }
}

