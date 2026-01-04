<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SubjectRequest;
use App\Models\Log;
use App\Models\Subject;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->toString();

        $subjects = Subject::when($search, function ($query, $term) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%");
            });
        })
            ->orderBy('name')
            ->paginate(7)
            ->withQueryString();

        return Inertia::render('Admin/Subjects/Index', [
            'subjects' => $subjects,
            'filters' => ['search' => $search],
        ]);
    }

    public function store(SubjectRequest $request)
    {
        $subject = Subject::create($request->validated());
        $this->logAction($request->user(), "Created subject {$subject->code}");

        return redirect()->back()->with('success', 'Subject created.');
    }

    public function update(SubjectRequest $request, Subject $subject)
    {
        $subject->update($request->validated());
        $this->logAction($request->user(), "Updated subject {$subject->code}");

        return redirect()->back()->with('success', 'Subject updated.');
    }

    public function destroy(Request $request, Subject $subject)
    {
        $code = $subject->code;
        $subject->delete();
        $this->logAction($request->user(), "Deleted subject {$code}");

        return redirect()->back()->with('success', 'Subject deleted.');
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

