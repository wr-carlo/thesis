<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Professor;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            'Computer Science',
            'Information Technology',
            'Information Systems',
        ];

        $departmentIds = [];
        foreach ($departments as $name) {
            $departmentIds[] = Department::firstOrCreate(['name' => $name])->id;
        }

        $sections = [
            ['department_id' => $departmentIds[0] ?? null, 'name' => 'BSCS-4A'],
            ['department_id' => $departmentIds[0] ?? null, 'name' => 'BSCS-4B'],
            ['department_id' => $departmentIds[1] ?? null, 'name' => 'BSIT-3A'],
            ['department_id' => $departmentIds[2] ?? null, 'name' => 'BSIS-2A'],
        ];

        foreach ($sections as $section) {
            if ($section['department_id']) {
                Section::firstOrCreate(
                    [
                        'department_id' => $section['department_id'],
                        'name' => $section['name'],
                    ]
                );
            }
        }

        $subjects = [
            ['name' => 'Data Structures', 'code' => 'CS201', 'description' => 'Fundamentals of data structures'],
            ['name' => 'Algorithms', 'code' => 'CS301', 'description' => 'Algorithm design and analysis'],
            ['name' => 'Operating Systems', 'code' => 'CS310', 'description' => 'Processes, threads, and memory management'],
            ['name' => 'Database Systems', 'code' => 'IT210', 'description' => 'Relational databases and SQL'],
        ];

        foreach ($subjects as $subject) {
            Subject::firstOrCreate(
                ['code' => $subject['code']],
                [
                    'name' => $subject['name'],
                    'description' => $subject['description'],
                ]
            );
        }

        // Instructors
        $instructors = [
            ['id_number' => 2001, 'name' => 'Alice Instructor', 'department_index' => 0],
            ['id_number' => 2002, 'name' => 'Bob Instructor', 'department_index' => 1],
        ];

        foreach ($instructors as $instructor) {
            $user = User::firstOrCreate(
                ['id_number' => $instructor['id_number']],
                [
                    'name' => $instructor['name'],
                    'role' => 'instructor',
                    'password' => Hash::make('chcc@2025'),
                ]
            );

            $departmentId = $departmentIds[$instructor['department_index']] ?? null;
            if ($departmentId) {
                Professor::firstOrCreate(
                    ['user_id' => $user->id],
                    ['department_id' => $departmentId]
                );
            }
        }

        // Students
        $studentEntries = [
            ['id_number' => 3001, 'name' => 'Charlie Student', 'section_name' => 'BSCS-4A'],
            ['id_number' => 3002, 'name' => 'Dana Student', 'section_name' => 'BSCS-4B'],
            ['id_number' => 3003, 'name' => 'Evan Student', 'section_name' => 'BSIT-3A'],
        ];

        foreach ($studentEntries as $entry) {
            $user = User::firstOrCreate(
                ['id_number' => $entry['id_number']],
                [
                    'name' => $entry['name'],
                    'role' => 'student',
                    'password' => Hash::make('chcc@2025'),
                ]
            );

            $section = Section::where('name', $entry['section_name'])->first();
            if ($section) {
                Student::firstOrCreate(
                    ['user_id' => $user->id],
                    ['section_id' => $section->id]
                );
            }
        }
    }
}

