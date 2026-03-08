<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SectionImportRequest;
use App\Http\Requests\Admin\SectionRequest;
use App\Models\Department;
use App\Models\Log;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SectionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->toString();
        $departmentId = $request->input('department_id');
        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);

        $sections = Section::with('department')
            ->when($search, function ($query, $term) {
                $query->where('name', 'like', "%{$term}%");
            })
            ->when($departmentId, function ($query, $id) {
                $query->where('department_id', $id);
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Admin/Sections/Index', [
            'sections' => $sections,
            'departments' => Department::orderBy('name')->get(),
            'filters' => [
                'search' => $search,
                'department_id' => $departmentId,
                'per_page' => $perPage,
            ],
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

    public function import(SectionImportRequest $request)
    {
        $file = $request->file('file');
        $departmentId = (int) $request->department_id;
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

            // Find name column (flexible: name, section, section name)
            $nameKey = null;
            foreach ($headers as $header) {
                $normalized = strtolower(trim($header));
                if (in_array($normalized, ['name', 'section', 'section name', 'section_name'])) {
                    $nameKey = $header;
                    break;
                }
            }

            if (!$nameKey) {
                $foundHeaders = implode(', ', array_map(fn($h) => '"' . $h . '"', $headers));
                return back()->with('flash', [
                    'type' => 'error',
                    'message' => "Invalid format. Required column: 'name' or 'section'. Found: {$foundHeaders}. Please download the template.",
                ]);
            }

            DB::beginTransaction();

            foreach ($allRows as $line) {
                $rowNumber++;
                $name = isset($line[$nameKey]) ? trim((string) $line[$nameKey]) : null;

                if (empty($name)) {
                    continue;
                }

                if (Section::where('department_id', $departmentId)->where('name', $name)->exists()) {
                    $errors[] = "Row {$rowNumber}: Section '{$name}' already exists";
                    $skipped++;
                    continue;
                }

                Section::create(['name' => $name, 'department_id' => $departmentId]);
                $this->logAction($request->user(), "Imported section {$name}");
                $imported++;
            }

            DB::commit();

            $message = $imported > 0
                ? "Successfully imported {$imported} section(s)." . ($skipped > 0 ? " {$skipped} skipped (duplicates)." : '')
                : "No sections imported." . ($skipped > 0 ? " {$skipped} row(s) skipped (duplicates)." : ' File may be empty or invalid.');

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
        $data = collect([
            ['name' => 'BSCS-4A'],
            ['name' => 'BSCS-4B'],
            ['name' => 'BSIT-3A'],
        ]);

        return (new FastExcel($data))->download('sections_template.xlsx');
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

