<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SectionRequest;
use App\Models\Department;
use App\Models\Log;
use App\Models\Section;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SectionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->toString();

        $sections = Section::with('department')
            ->when($search, function ($query, $term) {
                $query->where('name', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->paginate(7)
            ->withQueryString();

        return Inertia::render('Admin/Sections/Index', [
            'sections' => $sections,
            'departments' => Department::orderBy('name')->get(),
            'filters' => ['search' => $search],
        ]);
    }

    public function store(SectionRequest $request)
    {
        $section = Section::create($request->validated());
        $this->logAction($request->user(), "Created section {$section->name}");

        return redirect()->back()->with('success', 'Section created.');
    }

    public function update(SectionRequest $request, Section $section)
    {
        $section->update($request->validated());
        $this->logAction($request->user(), "Updated section {$section->name}");

        return redirect()->back()->with('success', 'Section updated.');
    }

    public function destroy(Request $request, Section $section)
    {
        $name = $section->name;
        $section->delete();
        $this->logAction($request->user(), "Deleted section {$name}");

        return redirect()->back()->with('success', 'Section deleted.');
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

