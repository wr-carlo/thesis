<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StudentImportRequest;
use App\Http\Requests\Admin\StudentStoreRequest;
use App\Http\Requests\Admin\StudentUpdateRequest;
use App\Models\Log as LogModel;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->toString();

        $students = User::with(['student.section'])
            ->where('role', 'student')
            ->when($search, function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('id_number', 'like', "%{$term}%");
                });
            })
            ->orderBy('name')
            ->paginate(8)
            ->withQueryString();

        return Inertia::render('Admin/Students/Index', [
            'students' => $students,
            'sections' => Section::orderBy('name')->get(),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Students/Create', [
            'sections' => Section::orderBy('name')->get(),
        ]);
    }

    public function store(StudentStoreRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'id_number' => $data['id_number'],
            'name' => $data['name'],
            'role' => 'student',
            'password' => Hash::make('chcc@2025'),
        ]);

        Student::create([
            'user_id' => $user->id,
            'section_id' => $data['section_id'],
        ]);

        $this->logAction($request->user(), "Created student {$user->name}");

        return redirect()->route('admin.students.index')->with('success', 'Student created.');
    }

    public function edit(User $student)
    {
        if ($student->role !== 'student') {
            abort(404);
        }

        $student->load(['student']);

        return Inertia::render('Admin/Students/Edit', [
            'student' => $student,
            'sections' => Section::orderBy('name')->get(),
        ]);
    }

    public function update(StudentUpdateRequest $request, User $student)
    {
        if ($student->role !== 'student') {
            abort(404);
        }

        $data = $request->validated();

        $student->id_number = $data['id_number'];
        $student->name = $data['name'];

        $student->save();

        Student::updateOrCreate(
            ['user_id' => $student->id],
            ['section_id' => $data['section_id']]
        );

        $this->logAction($request->user(), "Updated student {$student->name}");

        return redirect()->route('admin.students.index')->with('success', 'Student updated.');
    }

    public function resetPassword(Request $request, User $student)
    {
        if ($student->role !== 'student') {
            abort(404);
        }

        $student->password = Hash::make('chcc@2025');
        $student->save();

        $this->logAction($request->user(), "Reset password for student {$student->name}");

        return back()->with('success', 'Password reset successfully.');
    }

    public function destroy(Request $request, User $student)
    {
        if ($student->role !== 'student') {
            abort(404);
        }

        $name = $student->name;
        $student->delete();

        $this->logAction($request->user(), "Deleted student {$name}");

        return redirect()->route('admin.students.index')->with('success', 'Student deleted.');
    }

    public function import(StudentImportRequest $request)
    {
        $file = $request->file('file');
        $sectionId = $request->input('section_id');
        
        $imported = 0;
        $errors = [];
        $skipped = 0;
        $rowNumber = 1; // Start at 1 (header is row 1, data starts at row 2)

        try {
            // First, validate the headers
            $allRows = (new FastExcel)->import($file);
            if ($allRows->isEmpty()) {
               
                return back()->with('flash', [
                    'type' => 'error',
                    'message' => 'The Excel file is empty. Please upload a file with data.',
                ]);
            }

            // Get the first row to check headers
            $firstRow = $allRows->first();
            $headers = array_keys($firstRow);

            // Validate required headers exist (check for exact match and common variations)
            $hasIdNumber = false;
            $hasName = false;
            $idNumberKey = null;
            $nameKey = null;

            foreach ($headers as $header) {
                $normalizedHeader = strtolower(trim($header));
                if ($normalizedHeader === 'id_number' || $normalizedHeader === 'id number') {
                    $hasIdNumber = true;
                    $idNumberKey = $header;
                }
               
                if ($normalizedHeader === 'name') {
                    $hasName = true;
                    $nameKey = $header;
                }
            }

            if (!$hasIdNumber || !$hasName) {
                $foundHeaders = implode(', ', array_map(function($h) { return '"' . $h . '"'; }, $headers));
                return back()->with('flash', [
                    'type' => 'error',
                    'message' => "Invalid Excel format. Required columns: 'id_number' and 'name'. Found columns: {$foundHeaders}. Please download the template and follow the correct format.",
                ]);
            }
          
            // Validate header order (id_number should be first, name should be second)
            if ($headers[0] !== $idNumberKey) {
                return back()->with('flash', [
                    'type' => 'error',
                    'message' => 'Invalid column order. The first column must be "id_number" but found "' . $headers[0] . '". Current order: ' . implode(', ', $headers),
                ]);
            }

            if (count($headers) > 1 && $headers[1] !== $nameKey) {
                return back()->with('flash', [
                    'type' => 'error',
                    'message' => 'Invalid column order. The second column must be "name" but found "' . $headers[1] . '". Current order: ' . implode(', ', $headers),
                ]);
            }

            DB::beginTransaction();

            // Process each row
            foreach ($allRows as $line) {
                $rowNumber++;

                // Get values using the detected keys
                $idNumber = isset($line[$idNumberKey]) ? $line[$idNumberKey] : null;
                $name = isset($line[$nameKey]) ? $line[$nameKey] : null;

                // Skip completely empty rows
                if (empty($idNumber) && empty($name)) {
                    continue;
                }

                // Validate ID Number
                if (empty($idNumber) || trim($idNumber) === '') {
                    $errors[] = "Row {$rowNumber}: ID number is required";
                    $skipped++;
                    continue;
                }

                // Validate Name
                if (empty($name) || trim($name) === '') {
                    $errors[] = "Row {$rowNumber}: Name is required (ID: {$idNumber})";
                    $skipped++;
                    continue;
                }

                // Check if user already exists
                $existingUser = User::where('id_number', $idNumber)->first();
                if ($existingUser) {
                    $errors[] = "Row {$rowNumber}: ID number '{$idNumber}' already exists";
                    $skipped++;
                    continue;
                }

                // Create user
                $user = User::create([
                    'id_number' => trim($idNumber),
                    'name' => trim($name),
                    'role' => 'student',
                    'password' => Hash::make('chcc@2025'),
                ]);

                // Create student record
                Student::create([
                    'user_id' => $user->id,
                    'section_id' => $sectionId,
                ]);

                $imported++;
            }

            DB::commit();

            $this->logAction(
                $request->user(),
                "Imported {$imported} students" . ($skipped > 0 ? " ({$skipped} skipped)" : "")
            );

            if ($imported === 0) {
                return back()->with('flash', [
                    'type' => 'error',
                    'message' => 'No students were imported. Please check the errors below.',
                    'errors' => $errors,
                ]);
            }

            // If some students were imported, always show success
            $message = "Successfully imported {$imported} student(s)";
            if ($skipped > 0) {
                $message .= " ({$skipped} skipped)";
            }

            return back()->with('flash', [
                'type' => 'success',
                'message' => $message,
                'errors' => count($errors) > 0 ? $errors : null,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Import failed:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('flash', [
                'type' => 'error',
                'message' => 'Import failed: ' . $e->getMessage(),
            ]);
        }
    }

    public function downloadTemplate(): StreamedResponse
    {
        $data = [
            [
                'id_number' => '20210001',
                'name' => 'Juan Dela Cruz',
            ],
            [
                'id_number' => '20210002',
                'name' => 'Maria Santos',
            ],
            [
                'id_number' => '20210003',
                'name' => 'Jose Rizal',
            ],
        ];

        return (new FastExcel(collect($data)))
            ->download('student_import_template.xlsx');
    }

    private function logAction(User $actor, string $description): void
    {
        LogModel::create([
            'user_id' => $actor->id,
            'description' => $description,
            'role' => $actor->role,
        ]);
    }
}


