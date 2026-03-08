<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SubjectImportRequest;
use App\Http\Requests\Admin\SubjectRequest;
use App\Models\Log;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->toString();
        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);

        $subjects = Subject::when($search, function ($query, $term) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%");
            });
        })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Admin/Subjects/Index', [
            'subjects' => $subjects,
            'filters' => ['search' => $search, 'per_page' => $perPage],
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

    public function import(SubjectImportRequest $request)
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

            // Find subject_code column (flexible: subject_code, code)
            $codeKey = null;
            foreach ($headers as $header) {
                $normalized = strtolower(trim($header));
                if (in_array($normalized, ['subject_code', 'code', 'subject code'])) {
                    $codeKey = $header;
                    break;
                }
            }

            // Find subject_name column (flexible: subject_name, name)
            $nameKey = null;
            foreach ($headers as $header) {
                $normalized = strtolower(trim($header));
                if (in_array($normalized, ['subject_name', 'name', 'subject name'])) {
                    $nameKey = $header;
                    break;
                }
            }

            if (!$codeKey || !$nameKey) {
                $foundHeaders = implode(', ', array_map(fn($h) => '"' . $h . '"', $headers));
                return back()->with('flash', [
                    'type' => 'error',
                    'message' => "Invalid format. Required columns: 'subject_code' and 'subject_name'. Found: {$foundHeaders}. Please download the template.",
                ]);
            }

            DB::beginTransaction();

            foreach ($allRows as $line) {
                $rowNumber++;
                $code = isset($line[$codeKey]) ? trim((string) $line[$codeKey]) : null;
                $name = isset($line[$nameKey]) ? trim((string) $line[$nameKey]) : null;

                if (empty($code) || empty($name)) {
                    continue;
                }

                if (Subject::where('code', $code)->exists()) {
                    $errors[] = "Row {$rowNumber}: Subject code '{$code}' already exists";
                    $skipped++;
                    continue;
                }

                Subject::create([
                    'code' => $code,
                    'name' => $name,
                    'description' => null,
                ]);
                $this->logAction($request->user(), "Imported subject {$code}");
                $imported++;
            }

            DB::commit();

            $message = $imported > 0
                ? "Successfully imported {$imported} subject(s)." . ($skipped > 0 ? " {$skipped} skipped (duplicate codes)." : '')
                : "No subjects imported." . ($skipped > 0 ? " {$skipped} row(s) skipped (duplicate codes)." : ' File may be empty or invalid.');

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
            ['subject_code' => 'CS201', 'subject_name' => 'Data Structures'],
            ['subject_code' => 'CS301', 'subject_name' => 'Algorithms'],
            ['subject_code' => 'IT101', 'subject_name' => 'Introduction to Programming'],
        ]);

        return (new FastExcel($data))->download('subjects_template.xlsx');
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

