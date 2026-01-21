<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\StoreLessonRequest;
use App\Http\Requests\Instructor\StoreManualLessonRequest;
use App\Models\Assessment;
use App\Models\AssessmentItem;
use App\Models\Department;
use App\Models\Lesson;
use App\Models\Log as LogModel;
use App\Models\Subject;
use App\Services\AI\AIResponseParser;
use App\Services\AI\AIServiceManager;
use App\Services\Assessment\AssessmentGenerator;
use App\Services\FileProcessing\FileExtractorFactory;
use App\Services\FileProcessing\FileValidator;
use App\Services\FileProcessing\TextCleaner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class LessonController extends Controller
{
    public function __construct(
        protected FileValidator $fileValidator,
        protected TextCleaner $textCleaner,
        protected AIServiceManager $aiManager,
        protected AIResponseParser $aiParser,
        protected AssessmentGenerator $assessmentGenerator
    ) {}

    /**
     * Display a listing of lessons.
     */
    public function index(Request $request)
    {
        $professor = auth()->user()->professor;

        $query = Lesson::where('professor_id', $professor->id)
            ->with(['subject', 'assessments.items']);

        // Search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('subject', function ($subjectQuery) use ($search) {
                        $subjectQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Status filter
        if ($request->has('status') && $request->status && $request->status !== 'all') {
            $query->whereHas('assessments', function ($assessmentQuery) use ($request) {
                $assessmentQuery->where('status', $request->status);
            });
        }

        $lessons = $query->latest()->paginate(6)->withQueryString();

        return Inertia::render('Instructor/Lessons/Index', [
            'lessons' => $lessons,
            'filters' => [
                'search' => $request->search ?? '',
                'status' => $request->status ?? 'all',
            ],
        ]);
    }

    /**
     * Show the form for creating a new lesson.
     */
    public function create()
    {
        $professor = auth()->user()->professor;

        // Get all subjects assigned to this instructor
        $subjects = $professor->subjects()->get();

        return Inertia::render('Instructor/Lessons/Create', [
            'subjects' => $subjects,
        ]);
    }

    /**
     * Show the form for creating a manual assessment.
     */
    public function createManual()
    {
        $professor = auth()->user()->professor;

        // Get all subjects assigned to this instructor
        $subjects = $professor->subjects()->get();

        // Load all departments and sections for section assignment
        $departments = Department::all()->map(function ($department) {
            return [
                'id' => $department->id,
                'name' => $department->name,
            ];
        })->values()->all();

        $sections = \App\Models\Section::with('department')->get()->map(function ($section) {
            return [
                'id' => $section->id,
                'name' => $section->name,
                'department_id' => $section->department_id,
                'department' => $section->department ? [
                    'id' => $section->department->id,
                    'name' => $section->department->name,
                ] : null,
            ];
        })->values()->all();

        return Inertia::render('Instructor/Lessons/CreateManual', [
            'subjects' => $subjects,
            'departments' => $departments,
            'sections' => $sections,
        ]);
    }

    /**
     * Store a manually created lesson and assessment.
     */
    public function storeManual(StoreManualLessonRequest $request)
    {
        $professor = auth()->user()->professor;

        DB::beginTransaction();

        try {
            // Create lesson (no file, no extracted_content for manual creation)
            $lesson = Lesson::create([
                'subject_id' => $request->subject_id,
                'professor_id' => $professor->id,
                'title' => $request->title,
                'path' => '', // Empty string for manual creation (no file)
                'extracted_content' => null, // No extracted content for manual creation
            ]);

            // Create assessment
            $assessment = Assessment::create([
                'lesson_id' => $lesson->id,
                'title' => "Assessment for {$request->title}",
                'type' => 'regular',
                'status' => $request->input('status', 'draft'), // Default to draft if not provided
            ]);

            // Create assessment items from questions
            foreach ($request->questions as $questionData) {
                $itemData = [
                    'assessment_id' => $assessment->id,
                    'question' => $questionData['question'],
                    'type' => $questionData['type'],
                    'correct_answer' => $questionData['correct_answer'],
                ];

                // Add choices for multiple choice questions
                if ($questionData['type'] === 'multiple_choice' && isset($questionData['choices'])) {
                    $itemData['choices'] = $questionData['choices'];
                } else {
                    $itemData['choices'] = null;
                }

                AssessmentItem::create($itemData);
            }

            // Sync sections to assessment if provided
            if ($request->has('section_ids') && !empty($request->section_ids)) {
                $assessment->sections()->sync($request->section_ids);
            }

            // Log the manual assessment creation
            LogModel::create([
                'user_id' => auth()->id(),
                'description' => "Created manual assessment for {$request->title}",
                'role' => 'instructor',
            ]);

            DB::commit();

            return redirect()->route('instructor.lessons.index')
                ->with('success', 'Manual assessment created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Manual Assessment Creation Failed', [
                'error' => $e->getMessage(),
                'professor_id' => $professor->id,
                'exception' => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'error' => 'Failed to create assessment: ' . $e->getMessage(),
            ])->withInput();
        }
    }

    /**
     * Store a newly created lesson (temporarily in session for review).
     */
    public function store(StoreLessonRequest $request)
    {
        $professor = auth()->user()->professor;
        $file = null;
        $path = null;
        $filePath = null;

        try {
            $file = $request->file('file');

            // Stage 1: Upload file
            $path = $file->store('lessons', 'public');
            $filePath = storage_path("app/public/{$path}");

            Log::info('Lesson Processing: Upload Stage', [
                'stage' => 'upload',
                'status' => 'success',
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'lesson_title' => $request->title,
                'professor_id' => $professor->id,
            ]);

            // Stage 2: Validation
            $validation = $this->fileValidator->validateAll($file, $filePath);

            if (!$validation['valid']) {
                // Delete uploaded file if validation fails
                Storage::disk('public')->delete($path);

                Log::error('Lesson Processing: Validation Stage Failed', [
                    'stage' => 'validation',
                    'status' => 'failed',
                    'error' => $validation['error'],
                    'lesson_title' => $request->title,
                    'file_name' => $file->getClientOriginalName(),
                    'professor_id' => $professor->id,
                ]);

                throw new \Exception($validation['error']);
            }

            Log::info('Lesson Processing: Validation Stage', [
                'stage' => 'validation',
                'status' => 'success',
                'lesson_title' => $request->title,
                'file_name' => $file->getClientOriginalName(),
                'professor_id' => $professor->id,
            ]);

            // Stage 3: Text Extraction
            try {
                $mimeType = $file->getMimeType();
                $extractor = FileExtractorFactory::make($mimeType);
                $extractedText = $extractor->extract($filePath);

                // Clean text
                $cleanedText = $this->textCleaner->clean($extractedText);

                Log::info('Lesson Processing: Extraction Stage', [
                    'stage' => 'extraction',
                    'status' => 'success',
                    'text_length' => strlen($cleanedText),
                    'word_count' => str_word_count($cleanedText),
                    'lesson_title' => $request->title,
                    'file_name' => $file->getClientOriginalName(),
                    'professor_id' => $professor->id,
                ]);
            } catch (\Exception $e) {
                Storage::disk('public')->delete($path);

                Log::error('Lesson Processing: Extraction Stage Failed', [
                    'stage' => 'extraction',
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'lesson_title' => $request->title,
                    'file_name' => $file->getClientOriginalName(),
                    'professor_id' => $professor->id,
                    'exception' => $e->getTraceAsString(),
                ]);

                throw $e;
            }

            // Stage 4: AI Generation
            try {
                $config = [
                    'multiple_choice_count' => $request->multiple_choice_count,
                    'identification_count' => $request->identification_count,
                    'true_or_false_count' => $request->true_or_false_count,
                    'difficulty' => $request->difficulty,
                ];

                $aiResult = $this->aiManager->generateAssessment($cleanedText, $config);

                Log::info('Lesson Processing: AI Generation Stage', [
                    'stage' => 'ai_generation',
                    'status' => 'success',
                    'provider_used' => $aiResult['provider_used'] ?? 'unknown',
                    'chunks_processed' => $aiResult['chunks_processed'] ?? 1,
                    'retry_used' => $aiResult['retry_used'] ?? false,
                    'lesson_title' => $request->title,
                    'professor_id' => $professor->id,
                ]);
            } catch (\Exception $e) {
                Storage::disk('public')->delete($path);

                Log::error('Lesson Processing: AI Generation Stage Failed', [
                    'stage' => 'ai_generation',
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'lesson_title' => $request->title,
                    'professor_id' => $professor->id,
                    'exception' => $e->getTraceAsString(),
                ]);

                throw $e;
            }

            // Stage 5: Parse Response
            try {
                $parsedResponse = $this->aiParser->parse($aiResult['data']);

                Log::info('Lesson Processing: Parsing Stage', [
                    'stage' => 'parsing',
                    'status' => 'success',
                    'multiple_choice_count' => count($parsedResponse['multiple_choice'] ?? []),
                    'identification_count' => count($parsedResponse['identification'] ?? []),
                    'true_or_false_count' => count($parsedResponse['true_or_false'] ?? []),
                    'lesson_title' => $request->title,
                    'professor_id' => $professor->id,
                ]);
            } catch (\Exception $e) {
                Storage::disk('public')->delete($path);

                Log::error('Lesson Processing: Parsing Stage Failed', [
                    'stage' => 'parsing',
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'lesson_title' => $request->title,
                    'professor_id' => $professor->id,
                    'exception' => $e->getTraceAsString(),
                ]);

                throw $e;
            }

            // Store everything in session instead of database
            session([
                'lesson_review' => [
                    'subject_id' => $request->subject_id,
                    'professor_id' => $professor->id,
                    'title' => $request->title,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'extracted_content' => $cleanedText,
                    'assessment_title' => "Assessment for {$request->title}",
                    'questions' => $parsedResponse,
                    'ai_metadata' => [
                        'provider_used' => $aiResult['provider_used'] ?? null,
                        'chunks_processed' => $aiResult['chunks_processed'] ?? 1,
                        'retry_used' => $aiResult['retry_used'] ?? false,
                    ],
                ]
            ]);

            return redirect()->route('instructor.lessons.review')
                ->with('success', 'Assessment questions generated successfully! Please review before saving.');
        } catch (\Exception $e) {
            // Clean up uploaded file if it exists
            if (isset($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            Log::error('Lesson Processing: Overall Process Failed', [
                'stage' => 'overall',
                'status' => 'failed',
                'error' => $e->getMessage(),
                'lesson_title' => $request->title ?? 'unknown',
                'file_name' => $file?->getClientOriginalName() ?? 'unknown',
                'professor_id' => $professor->id ?? null,
                'exception' => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'error' => 'Failed to process lesson: ' . $e->getMessage(),
            ])->withInput();
        }
    }

    /**
     * Display the review page for generated assessment questions.
     */
    public function review()
    {
        $sessionData = session('lesson_review');

        if (!$sessionData) {
            return redirect()->route('instructor.lessons.create')
                ->withErrors(['error' => 'No lesson data found. Please upload a lesson first.']);
        }

        // Load subject information for display
        $subject = Subject::find($sessionData['subject_id']);

        // Transform questions from session format to display format
        $items = [];

        if (isset($sessionData['questions']['multiple_choice'])) {
            foreach ($sessionData['questions']['multiple_choice'] as $question) {
                // Ensure choices is an array, not a string
                $choices = $question['choices'] ?? [];
                if (is_string($choices)) {
                    $choices = json_decode($choices, true) ?? [];
                }
                if (!is_array($choices)) {
                    $choices = [];
                }

                $items[] = [
                    'question' => $question['question'],
                    'type' => 'multiple_choice',
                    'choices' => $choices,
                    'correct_answer' => $question['correct_answer'] ?? '',
                ];
            }
        }

        if (isset($sessionData['questions']['identification'])) {
            foreach ($sessionData['questions']['identification'] as $question) {
                $items[] = [
                    'question' => $question['question'],
                    'type' => 'identification',
                    'choices' => null,
                    'correct_answer' => $question['correct_answer'] ?? '',
                ];
            }
        }

        if (isset($sessionData['questions']['true_or_false'])) {
            foreach ($sessionData['questions']['true_or_false'] as $question) {
                $items[] = [
                    'question' => $question['question'],
                    'type' => 'true_or_false',
                    'choices' => null,
                    'correct_answer' => $question['correct_answer'] ?? '',
                ];
            }
        }

        // Load all departments - explicitly serialize to ensure proper structure
        $departments = Department::all()->map(function ($department) {
            return [
                'id' => $department->id,
                'name' => $department->name,
            ];
        })->values()->all();

        // Load sections and ensure department_id is included in the serialization
        $sections = \App\Models\Section::with('department')->get()->map(function ($section) {
            return [
                'id' => $section->id,
                'name' => $section->name,
                'department_id' => $section->department_id,
                'department' => $section->department ? [
                    'id' => $section->department->id,
                    'name' => $section->department->name,
                ] : null,
            ];
        })->values()->all();

        return Inertia::render('Instructor/Lessons/Review', [
            'lesson' => [
                'title' => $sessionData['title'],
                'subject' => $subject,
            ],
            'items' => $items,
            'departments' => $departments,
            'sections' => $sections,
            'selectedSectionIds' => [], // Empty for new assessments
        ]);
    }

    /**
     * Save lesson and assessment from review session to database.
     */
    public function saveFromReview(Request $request)
    {
        $sessionData = session('lesson_review');

        if (!$sessionData) {
            return redirect()->route('instructor.lessons.create')
                ->withErrors(['error' => 'Session expired. Please upload the lesson again.']);
        }

        DB::beginTransaction();

        try {
            // Validate section_ids if provided
            if ($request->has('section_ids')) {
                $request->validate([
                    'section_ids' => 'nullable|array',
                    'section_ids.*' => 'exists:sections,id',
                ]);
            }

            // Validate questions if provided in request (from edited questions)
            if ($request->has('items')) {
                $request->validate([
                    'items' => 'required|array',
                    'items.*.question' => 'required|string',
                    'items.*.type' => 'required|in:multiple_choice,identification,true_or_false',
                    'items.*.choices' => 'nullable|array',
                    'items.*.correct_answer' => 'required|string',
                ]);

                // Use edited questions from request
                $questions = [
                    'multiple_choice' => [],
                    'identification' => [],
                    'true_or_false' => [],
                ];

                foreach ($request->items as $item) {
                    $questionData = [
                        'question' => $item['question'],
                        'correct_answer' => $item['correct_answer'],
                    ];

                    if ($item['type'] === 'multiple_choice' && isset($item['choices'])) {
                        $questionData['choices'] = $item['choices'];
                        $questions['multiple_choice'][] = $questionData;
                    } elseif ($item['type'] === 'identification') {
                        $questions['identification'][] = $questionData;
                    } elseif ($item['type'] === 'true_or_false') {
                        $questions['true_or_false'][] = $questionData;
                    }
                }

                $sessionData['questions'] = $questions;
            }

            // Create lesson record
            $lesson = Lesson::create([
                'subject_id' => $sessionData['subject_id'],
                'professor_id' => $sessionData['professor_id'],
                'title' => $sessionData['title'],
                'path' => $sessionData['file_path'],
                'extracted_content' => $sessionData['extracted_content'],
            ]);

            // Log upload (retrospective)
            Log::info('Lesson Processing: Upload Stage', [
                'stage' => 'upload',
                'status' => 'success',
                'message' => 'File uploaded successfully',
                'lesson_id' => $lesson->id,
                'file_name' => $sessionData['file_name'] ?? null,
                'file_size' => $sessionData['file_size'] ?? null,
                'mime_type' => $sessionData['mime_type'] ?? null,
            ]);

            // Log validation (retrospective)
            Log::info('Lesson Processing: Validation Stage', [
                'stage' => 'validation',
                'status' => 'success',
                'message' => 'File validation passed',
                'lesson_id' => $lesson->id,
            ]);

            // Log extraction (retrospective)
            Log::info('Lesson Processing: Extraction Stage', [
                'stage' => 'extraction',
                'status' => 'success',
                'message' => 'Text extracted successfully',
                'lesson_id' => $lesson->id,
                'text_length' => strlen($sessionData['extracted_content']),
                'word_count' => str_word_count($sessionData['extracted_content']),
            ]);

            // Log AI generation (retrospective)
            if (isset($sessionData['ai_metadata'])) {
                Log::info('Lesson Processing: AI Generation Stage', [
                    'stage' => 'ai_call',
                    'status' => 'success',
                    'message' => 'AI generation successful',
                    'lesson_id' => $lesson->id,
                    'provider' => $sessionData['ai_metadata']['provider_used'] ?? 'unknown',
                    'chunks_processed' => $sessionData['ai_metadata']['chunks_processed'] ?? 1,
                    'retry_used' => $sessionData['ai_metadata']['retry_used'] ?? false,
                ]);
            }

            // Log parsing (retrospective)
            $questions = $sessionData['questions'];
            Log::info('Lesson Processing: Parsing Stage', [
                'stage' => 'parsing',
                'status' => 'success',
                'message' => 'Response parsed successfully',
                'lesson_id' => $lesson->id,
                'multiple_choice_count' => count($questions['multiple_choice'] ?? []),
                'identification_count' => count($questions['identification'] ?? []),
                'true_or_false_count' => count($questions['true_or_false'] ?? []),
            ]);

            // Create assessment and items
            $assessment = $this->assessmentGenerator->generate(
                $lesson,
                $sessionData['questions'],
                ['title' => $sessionData['assessment_title']]
            );

            // Sync sections to assessment
            if ($request->has('section_ids')) {
                $assessment->sections()->sync($request->section_ids ?? []);
            }

            // Log saving
            Log::info('Lesson Processing: Saving Stage', [
                'stage' => 'saving',
                'status' => 'success',
                'message' => 'Assessment saved successfully',
                'lesson_id' => $lesson->id,
                'assessment_id' => $assessment->id,
                'total_items' => $assessment->items->count(),
                'sections_assigned' => $assessment->sections()->count(),
            ]);

            // Log the generated assessment creation
            LogModel::create([
                'user_id' => auth()->id(),
                'description' => "Created generated assessment for {$sessionData['title']}",
                'role' => 'instructor',
            ]);

            DB::commit();

            // Clear session
            session()->forget('lesson_review');

            return redirect()->route('instructor.lessons.index')
                ->with('success', 'Lesson and assessment saved successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log error with lesson ID if available
            $lessonId = isset($lesson) ? $lesson->id : null;

            Log::error('Lesson Processing: Saving Stage Failed', [
                'stage' => 'saving',
                'status' => 'failed',
                'error' => $e->getMessage(),
                'lesson_id' => $lessonId,
                'lesson_title' => $sessionData['title'] ?? 'unknown',
                'exception' => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'error' => 'Failed to save lesson: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Cancel review and cleanup uploaded file and session.
     */
    public function cancelReview()
    {
        $sessionData = session('lesson_review');

        if ($sessionData && isset($sessionData['file_path'])) {
            // Delete uploaded file
            if (Storage::disk('public')->exists($sessionData['file_path'])) {
                Storage::disk('public')->delete($sessionData['file_path']);
            }
        }

        // Clear session
        session()->forget('lesson_review');

        return redirect()->route('instructor.lessons.create')
            ->with('flash', [
                'type' => 'info',
                'message' => 'Lesson upload cancelled. No changes were saved.',
            ]);
    }

    /**
     * Show the form for editing the specified lesson.
     */
    public function edit(Lesson $lesson)
    {
        // Check authorization
        if ($lesson->professor_id !== auth()->user()->professor->id) {
            abort(403);
        }

        $lesson->load(['subject', 'assessments.items', 'assessments.sections']);

        // Get all departments - explicitly serialize to ensure proper structure
        $departments = Department::all()->map(function ($department) {
            return [
                'id' => $department->id,
                'name' => $department->name,
            ];
        })->values()->all();

        // Load sections and ensure department_id is included in the serialization
        $sections = \App\Models\Section::with('department')->get()->map(function ($section) {
            return [
                'id' => $section->id,
                'name' => $section->name,
                'department_id' => $section->department_id,
                'department' => $section->department ? [
                    'id' => $section->department->id,
                    'name' => $section->department->name,
                ] : null,
            ];
        })->values()->all();

        // Get currently assigned section IDs
        $selectedSectionIds = $lesson->assessments->first()?->sections->pluck('id')->toArray() ?? [];

        return Inertia::render('Instructor/Lessons/Edit', [
            'lesson' => $lesson,
            'departments' => $departments,
            'sections' => $sections,
            'selectedSectionIds' => $selectedSectionIds,
        ]);
    }

    /**
     * Update the specified lesson assessment.
     */
    public function update(Request $request, Lesson $lesson)
    {
        // Check authorization
        if ($lesson->professor_id !== auth()->user()->professor->id) {
            abort(403);
        }

        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'sometimes|exists:assessment_items,id',
            'items.*.question' => 'required|string',
            'items.*.type' => 'required|in:multiple_choice,identification,true_or_false',
            'items.*.choices' => 'nullable|array',
            'items.*.correct_answer' => 'required|string',
            'section_ids' => 'nullable|array',
            'section_ids.*' => 'exists:sections,id',
        ]);

        DB::beginTransaction();

        try {
            $assessment = $lesson->assessments()->first();

            if (!$assessment) {
                throw new \Exception('No assessment found for this lesson');
            }

            // Delete existing items
            $assessment->items()->delete();

            // Create new/updated items
            foreach ($request->items as $item) {
                $assessment->items()->create([
                    'question' => $item['question'],
                    'type' => $item['type'],
                    'choices' => isset($item['choices']) ? json_encode($item['choices']) : null,
                    'correct_answer' => $item['correct_answer'],
                ]);
            }

            // Sync sections to assessment
            if ($request->has('section_ids')) {
                $assessment->sections()->sync($request->section_ids ?? []);
            }

            DB::commit();

            return redirect()->route('instructor.lessons.index')->with('success', 'Assessment updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors([
                'error' => 'Failed to update assessment: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Publish the assessment.
     */
    public function publish(Lesson $lesson)
    {
        // Check authorization
        if ($lesson->professor_id !== auth()->user()->professor->id) {
            abort(403);
        }

        $assessment = $lesson->assessments()->first();

        if ($assessment) {
            $assessment->update(['status' => 'published']);

            return back()->with('success', 'Assessment published successfully!');
        }

        return back()->withErrors(['error' => 'No assessment found']);
    }

    /**
     * Unpublish the assessment (set to draft).
     */
    public function unpublish(Lesson $lesson)
    {
        // Check authorization
        if ($lesson->professor_id !== auth()->user()->professor->id) {
            abort(403);
        }

        $assessment = $lesson->assessments()->first();

        if ($assessment) {
            $assessment->update(['status' => 'draft']);

            return back()->with('success', 'Assessment set to draft!');
        }

        return back()->withErrors(['error' => 'No assessment found']);
    }

    /**
     * Remove the specified lesson.
     */
    public function destroy(Lesson $lesson)
    {
        // Check authorization
        if ($lesson->professor_id !== auth()->user()->professor->id) {
            abort(403);
        }

        try {
            // Delete file from storage
            if ($lesson->path && Storage::disk('public')->exists($lesson->path)) {
                Storage::disk('public')->delete($lesson->path);
            }

            // Delete lesson (cascade deletes assessments and logs)
            $lesson->delete();

            return redirect()->route('instructor.lessons.index')
                ->with('success', 'Lesson deleted successfully!');
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Failed to delete lesson: ' . $e->getMessage(),
            ]);
        }
    }
}
