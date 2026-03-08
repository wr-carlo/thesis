<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InstructorImportRequest;
use App\Http\Requests\Admin\InstructorStoreRequest;
use App\Http\Requests\Admin\InstructorUpdateRequest;
use App\Models\Department;
use App\Models\Log;
use App\Models\Professor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InstructorController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->toString();
        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);

        $instructors = User::with(['professor.department'])
            ->where('role', 'instructor')
            ->when($search, function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('id_number', 'like', "%{$term}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Admin/Instructors/Index', [
            'instructors' => $instructors,
            'departments' => Department::orderBy('name')->get(),
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Instructors/Create', [
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function store(InstructorStoreRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'id_number' => $data['id_number'],
            'name' => $data['name'],
            'role' => 'instructor',
            'password' => Hash::make('chcc@2025'),
        ]);

        Professor::create([
            'user_id' => $user->id,
            'department_id' => $data['department_id'],
        ]);

        $this->logAction($request->user(), "Created instructor {$user->name}");

        return redirect()->route('admin.instructors.index')->with('success', 'Instructor created.');
    }

    public function edit(User $instructor)
    {
        if ($instructor->role !== 'instructor') {
            abort(404);
        }

        $instructor->load(['professor']);

        return Inertia::render('Admin/Instructors/Edit', [
            'instructor' => $instructor,
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function update(InstructorUpdateRequest $request, User $instructor)
    {
        if ($instructor->role !== 'instructor') {
            abort(404);
        }

        $data = $request->validated();

        $instructor->id_number = $data['id_number'];
        $instructor->name = $data['name'];

        $instructor->save();

        Professor::updateOrCreate(
            ['user_id' => $instructor->id],
            ['department_id' => $data['department_id']]
        );

        $this->logAction($request->user(), "Updated instructor {$instructor->name}");

        return redirect()->route('admin.instructors.index')->with('success', 'Instructor updated.');
    }

    public function resetPassword(Request $request, User $instructor)
    {
        if ($instructor->role !== 'instructor') {
            abort(404);
        }

        $instructor->password = Hash::make('chcc@2025');
        $instructor->save();

        $this->logAction($request->user(), "Reset password for instructor {$instructor->name}");

        return back()->with('success', 'Password reset successfully.');
    }

    public function destroy(Request $request, User $instructor)
    {
        if ($instructor->role !== 'instructor') {
            abort(404);
        }

        $name = $instructor->name;
        $instructor->delete();

        $this->logAction($request->user(), "Deleted instructor {$name}");

        return redirect()->route('admin.instructors.index')->with('success', 'Instructor deleted.');
    }

    public function import(InstructorImportRequest $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '256M');

        $file = $request->file('file');
        $departmentId = $request->input('department_id');
        $imported = 0;
        $errors = [];
        $skipped = 0;
        $rowNumber = 1;

        try {
            $allRows = (new FastExcel)->import($file);

            if ($allRows->isEmpty()) {
                return back()->with('flash', [
                    'type' => 'error',
                    'message' => 'The Excel file is empty. Please upload a file with data.',
                ]);
            }

            $firstRow = $allRows->first();
            $headers = array_keys($firstRow);
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

            if (! $hasIdNumber || ! $hasName) {
                $foundHeaders = implode(', ', array_map(fn ($h) => '"' . $h . '"', $headers));
                return back()->with('flash', [
                    'type' => 'error',
                    'message' => "Invalid Excel format. Required columns: 'id_number' and 'name'. Found columns: {$foundHeaders}. Please download the template and follow the correct format.",
                ]);
            }

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

            // Hash password once (reused for all rows)
            $hashedPassword = Hash::make('chcc@2025');

            // Pre-load existing id_numbers (1 query instead of N)
            $existingIdNumbers = User::pluck('id_number')->flip()->toArray();

            DB::beginTransaction();

            $chunkSize = 50;
            $userBatch = [];
            $now = now();

            foreach ($allRows as $line) {
                $rowNumber++;
                $idNumber = isset($line[$idNumberKey]) ? trim((string) $line[$idNumberKey]) : null;
                $name = isset($line[$nameKey]) ? trim((string) $line[$nameKey]) : null;

                if (empty($idNumber) && empty($name)) {
                    continue;
                }

                if (empty($idNumber)) {
                    $errors[] = "Row {$rowNumber}: ID number is required";
                    $skipped++;
                    continue;
                }

                if (empty($name)) {
                    $errors[] = "Row {$rowNumber}: Name is required (ID: {$idNumber})";
                    $skipped++;
                    continue;
                }

                if (isset($existingIdNumbers[$idNumber])) {
                    $errors[] = "Row {$rowNumber}: ID number '{$idNumber}' already exists";
                    $skipped++;
                    continue;
                }
                $existingIdNumbers[$idNumber] = true;

                $userBatch[] = [
                    'id_number' => $idNumber,
                    'name' => $name,
                    'role' => 'instructor',
                    'password' => $hashedPassword,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($userBatch) >= $chunkSize) {
                    User::insert($userBatch);
                    $firstId = DB::connection()->getPdo()->lastInsertId();
                    $userIds = range((int) $firstId, (int) $firstId + count($userBatch) - 1);
                    $professorBatch = array_map(fn ($uid) => [
                        'user_id' => $uid,
                        'department_id' => $departmentId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ], $userIds);
                    Professor::insert($professorBatch);
                    $imported += count($userBatch);
                    $userBatch = [];
                }
            }

            if (! empty($userBatch)) {
                User::insert($userBatch);
                $firstId = DB::connection()->getPdo()->lastInsertId();
                $userIds = range((int) $firstId, (int) $firstId + count($userBatch) - 1);
                $professorBatch = array_map(fn ($uid) => [
                    'user_id' => $uid,
                    'department_id' => $departmentId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $userIds);
                Professor::insert($professorBatch);
                $imported += count($userBatch);
            }

            DB::commit();

            $this->logAction(
                $request->user(),
                "Imported {$imported} instructors" . ($skipped > 0 ? " ({$skipped} skipped)" : "")
            );

            if ($imported === 0) {
                return back()->with('flash', [
                    'type' => 'error',
                    'message' => 'No instructors were imported. Please check the errors below.',
                    'errors' => $errors,
                ]);
            }

            // If some instructors were imported, always show success
            $message = "Successfully imported {$imported} instructor(s)";
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
                'id_number' => '10001',
                'name' => 'Prof. Juan Dela Cruz',
            ],
            [
                'id_number' => '10002',
                'name' => 'Prof. Maria Santos',
            ],
            [
                'id_number' => '10003',
                'name' => 'Prof. Jose Rizal',
            ],
        ];

        return (new FastExcel(collect($data)))
            ->download('instructor_import_template.xlsx');
    }

    private function logAction(User $actor, string $description): void
    {
        Log::create([
            'user_id' => $actor->id,
            'description' => $description,
            'role' => $actor->role,
        ]);
    }
}

