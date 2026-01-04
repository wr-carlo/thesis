<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DepartmentRequest;
use App\Models\Department;
use App\Models\Log;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->toString();

        $departments = Department::when($search, function ($query, $term) {
            $query->where('name', 'like', "%{$term}%");
        })
            ->orderBy('name')
            ->paginate(7)
            ->withQueryString();

        return Inertia::render('Admin/Departments/Index', [
            'departments' => $departments,
            'filters' => ['search' => $search],
        ]);
    }

    public function store(DepartmentRequest $request)
    {
        $department = Department::create($request->validated());
        $this->logAction($request->user(), "Created department {$department->name}");

        return redirect()->back()->with('success', 'Department created.');
    }

    public function update(DepartmentRequest $request, Department $department)
    {
        $department->update($request->validated());
        $this->logAction($request->user(), "Updated department {$department->name}");

        return redirect()->back()->with('success', 'Department updated.');
    }

    public function destroy(Request $request, Department $department)
    {
        $name = $department->name;
        $department->delete();
        $this->logAction($request->user(), "Deleted department {$name}");

        return redirect()->back()->with('success', 'Department deleted.');
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

