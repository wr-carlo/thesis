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
        $subjects = Subject::with(['professorSubjects.professor.user', 'professorSubjects.professor.department'])
            ->orderBy('name')
            ->get()
            ->map(function ($subject) {
                return [
                    'id' => $subject->id,
                    'code' => $subject->code,
                    'name' => $subject->name,
                    'assignments' => $subject->professorSubjects->map(function ($ps) {
                        return [
                            'id' => $ps->id,
                            'professor_id' => $ps->professor_id,
                            'professor_name' => $ps->professor->user->name ?? 'Unknown',
                            'department_name' => $ps->professor->department->name ?? 'No Department',
                            'created_at' => $ps->created_at?->toISOString(),
                        ];
                    }),
                ];
            });

        return Inertia::render('Admin/ProfessorSubjects/Index', [
            'subjects' => $subjects,
            'professors' => Professor::with(['user', 'department'])->orderBy('id')->get(),
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

    public function update(ProfessorSubjectRequest $request, ProfessorSubject $assignment)
    {
        $data = $request->validated();

        $assignment->update([
            'professor_id' => $data['professor_id'],
            'subject_id' => $data['subject_id'],
        ]);

        $this->logAction($request->user(), "Updated assignment: Instructor {$assignment->professor_id} / subject {$assignment->subject_id}");

        return redirect()->back()->with('success', 'Assignment updated successfully.');
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
