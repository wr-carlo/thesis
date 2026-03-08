<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DepartmentImportRequest;
use App\Http\Requests\Admin\DepartmentRequest;
use App\Models\Department;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->toString();
        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);

        $departments = Department::when($search, function ($query, $term) {
            $query->where('name', 'like', "%{$term}%");
        })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Admin/Departments/Index', [
            'departments' => $departments,
            'filters' => ['search' => $search, 'per_page' => $perPage],
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

    public function import(DepartmentImportRequest $request)
    {
        $file = $request->file('file');
        $imported = 0;
        $errors = [];
        $skipped = 0;
        $rowNumber = 1;

        try {
            $allRows = (new FastExcel)->import($file);
            if ($allRows->isEmpty()) {
                return back()->with('flash', [
                    'type' => 'error',
                    'message' => 'The file is empty. Please upload a file with data.',
                ]);
            }

            $firstRow = $allRows->first();
            $headers = array_keys($firstRow);

            // Find name column (flexible: name, department, department name)
            $nameKey = null;
            foreach ($headers as $header) {
                $normalized = strtolower(trim($header));
                if (in_array($normalized, ['name', 'department', 'department name', 'department_name'])) {
                    $nameKey = $header;
                    break;
                }
            }

            if (!$nameKey) {
                $foundHeaders = implode(', ', array_map(fn($h) => '"' . $h . '"', $headers));
                return back()->with('flash', [
                    'type' => 'error',
                    'message' => "Invalid format. Required column: 'name' or 'department'. Found: {$foundHeaders}. Please download the template.",
                ]);
            }

            DB::beginTransaction();

            foreach ($allRows as $line) {
                $rowNumber++;
                $name = isset($line[$nameKey]) ? trim((string) $line[$nameKey]) : null;

                if (empty($name)) {
                    continue;
                }

                if (Department::where('name', $name)->exists()) {
                    $errors[] = "Row {$rowNumber}: Department '{$name}' already exists";
                    $skipped++;
                    continue;
                }

                Department::create(['name' => $name]);
                $this->logAction($request->user(), "Imported department {$name}");
                $imported++;
            }

            DB::commit();

            $message = $imported > 0
                ? "Successfully imported {$imported} department(s)." . ($skipped > 0 ? " {$skipped} skipped (duplicates)." : '')
                : "No departments imported." . ($skipped > 0 ? " {$skipped} row(s) skipped (duplicates or invalid)." : ' File may be empty or invalid.');

            return back()->with('flash', [
                'type' => $imported > 0 ? 'success' : 'warning',
                'message' => $message,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('flash', [
                'type' => 'error',
                'message' => 'Import failed: ' . $e->getMessage(),
                'errors' => $errors,
            ]);
        }
    }

    public function downloadTemplate(): StreamedResponse
    {
        $data = [
            ['name' => 'Information Technology'],
            ['name' => 'Computer Science'],
            ['name' => 'Engineering'],
        ];

        return (new FastExcel(collect($data)))->download('departments_template.xlsx');
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

