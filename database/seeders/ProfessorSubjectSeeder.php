<?php

namespace Database\Seeders;

use App\Models\Professor;
use App\Models\ProfessorSubject;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProfessorSubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * This seeder assigns existing subjects to existing instructors.
     * Modify the $assignments array to customize subject-instructor mappings.
     */
    public function run(): void
    {
        // Define subject assignments
        // Format: ['instructor_id_number' => ['subject_codes' => [...], 'subject_names' => [...]]]
        $assignments = [
            // Alice Instructor (2001) - Computer Science department
            '2001' => [
                'subject_codes' => ['CS201', 'CS301'], // Data Structures, Algorithms
            ],
            // Bob Instructor (2002) - IT department
            '2002' => [
                'subject_codes' => ['IT210', 'CS310'], // Database Systems, Operating Systems
            ],
        ];

        foreach ($assignments as $instructorIdNumber => $config) {
            // Find instructor by ID number
            $user = User::where('id_number', $instructorIdNumber)
                ->where('role', 'instructor')
                ->first();

            if (!$user) {
                $this->command->warn("Instructor with ID number {$instructorIdNumber} not found. Skipping...");
                continue;
            }

            $professor = Professor::where('user_id', $user->id)->first();

            if (!$professor) {
                $this->command->warn("Professor record not found for instructor {$user->name} (ID: {$instructorIdNumber}). Skipping...");
                continue;
            }

            // Get subjects by codes if specified
            $subjects = collect();
            
            if (isset($config['subject_codes']) && !empty($config['subject_codes'])) {
                $subjectsByCode = Subject::whereIn('code', $config['subject_codes'])->get();
                $subjects = $subjects->merge($subjectsByCode);
            }

            // Get subjects by names if specified
            if (isset($config['subject_names']) && !empty($config['subject_names'])) {
                $subjectsByName = Subject::whereIn('name', $config['subject_names'])->get();
                $subjects = $subjects->merge($subjectsByName);
            }

            // Remove duplicates
            $subjects = $subjects->unique('id');

            if ($subjects->isEmpty()) {
                $this->command->warn("No subjects found for instructor {$user->name} (ID: {$instructorIdNumber}). Skipping...");
                continue;
            }

            // Assign subjects to instructor
            foreach ($subjects as $subject) {
                ProfessorSubject::firstOrCreate(
                    [
                        'professor_id' => $professor->id,
                        'subject_id' => $subject->id,
                    ]
                );

                $this->command->info("Assigned subject '{$subject->name}' ({$subject->code}) to instructor '{$user->name}'");
            }
        }

        // Alternative: Assign all existing subjects to all instructors (uncomment if needed)
        /*
        $professors = Professor::with('user')->get();
        $subjects = Subject::all();

        foreach ($professors as $professor) {
            foreach ($subjects as $subject) {
                ProfessorSubject::firstOrCreate(
                    [
                        'professor_id' => $professor->id,
                        'subject_id' => $subject->id,
                    ]
                );
            }
        }

        $this->command->info("Assigned all subjects to all instructors.");
        */
    }
}
