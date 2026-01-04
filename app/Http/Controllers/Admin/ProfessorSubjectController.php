<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProfessorSubjectRequest;
use App\Models\Log;
use App\Models\Professor;
use App\Models\ProfessorSubject;
use App\Models\Subject;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProfessorSubjectController extends Controller
{
    public function index()
    {
        $assignments = ProfessorSubject::with(['professor.user', 'subject'])
            ->orderByDesc('id')
            ->paginate(7);

        return Inertia::render('Admin/ProfessorSubjects/Index', [
            'assignments' => $assignments,
            'professors' => Professor::with(['user', 'department'])->orderBy('id')->get(),
            'subjects' => Subject::orderBy('name')->get(),
        ]);
    }

    public function store(ProfessorSubjectRequest $request)
    {
        $data = $request->validated();

        $assignment = ProfessorSubject::firstOrCreate(
            [
                'professor_id' => $data['professor_id'],
                'subject_id' => $data['subject_id'],
            ]
        );

        $this->logAction($request->user(), "Assigned professor {$assignment->professor_id} to subject {$assignment->subject_id}");

        return redirect()->back()->with('success', 'Assignment saved.');
    }

    public function destroy(Request $request, ProfessorSubject $assignment)
    {
        $info = "Instructor {$assignment->professor_id} / subject {$assignment->subject_id}";
        $assignment->delete();
        $this->logAction($request->user(), "Removed assignment {$info}");

        return redirect()->back()->with('success', 'Assignment removed.');
    }

    private function logAction($actor, string $description): void
    {
        Log::create([
            'user_id' => $actor->id,
            'description' => $description,
            'role' => $actor->role,
        ]);
    }
}

