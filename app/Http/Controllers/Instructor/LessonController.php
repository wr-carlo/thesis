<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\StoreLessonRequest;
use App\Models\Lesson;
use App\Models\Subject;
use App\Services\AI\AIResponseParser;
use App\Services\AI\AIServiceManager;
use App\Services\Assessment\AssessmentGenerator;
use App\Services\FileProcessing\FileExtractorFactory;
use App\Services\FileProcessing\FileValidator;
use App\Services\FileProcessing\TextCleaner;
use App\Services\Logging\ProcessingLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
class LessonController extends Controller
{
    public function __construct(
        protected FileValidator $fileValidator,
        protected TextCleaner $textCleaner,
        protected AIServiceManager $aiManager,
        protected AIResponseParser $aiParser,
        protected AssessmentGenerator $assessmentGenerator,
        protected ProcessingLogger $logger
    ) {}

    /**
     * Display a listing of lessons.
     */
    public function index()
    {
        $professor = auth()->user()->professor;
        
        $lessons = Lesson::where('professor_id', $professor->id)
            ->with(['subject', 'assessments'])
            ->latest()
            ->paginate(15);

        return Inertia::render('Instructor/Lessons/Index', [
            'lessons' => $lessons,
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
     * Store a newly created lesson.
     */
    public function store(StoreLessonRequest $request)
    {
        DB::beginTransaction();
        
        try {
            $professor = auth()->user()->professor;
            $file = $request->file('file');
            
            // Stage 1: Upload file
            $path = $file->store('lessons', 'public');
            $filePath = storage_path("app/public/{$path}");

            // Create lesson record
            $lesson = Lesson::create([
                'subject_id' => $request->subject_id,
                'professor_id' => $professor->id,
                'title' => $request->title,
                'path' => $path,
            ]);

            $this->logger->logSuccess($lesson, 'upload', 'File uploaded successfully', [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);

            // Stage 2: Validation
            try {
                $validation = $this->fileValidator->validateAll($file, $filePath);
                
                if (!$validation['valid']) {
                    throw new \Exception($validation['error']);
                }

                $this->logger->logSuccess($lesson, 'validation', 'File validation passed');

            } catch (\Exception $e) {
                $this->logger->logError($lesson, 'validation', $e->getMessage());
                throw $e;
            }

            // Stage 3: Text Extraction
            try {
                $mimeType = $file->getMimeType();
                $extractor = FileExtractorFactory::make($mimeType);
                $extractedText = $extractor->extract($filePath);
                
                // Clean text
                $cleanedText = $this->textCleaner->clean($extractedText);
                
                // Save extracted content
                $lesson->update(['extracted_content' => $cleanedText]);

                $this->logger->logSuccess($lesson, 'extraction', 'Text extracted successfully', [
                    'text_length' => strlen($cleanedText),
                    'word_count' => str_word_count($cleanedText),
                ]);

            } catch (\Exception $e) {
                $this->logger->logError($lesson, 'extraction', $e->getMessage());
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

                $this->logger->logAICall(
                    $lesson,
                    $aiResult['provider_used'],
                    'success',
                    'AI generation successful',
                    [
                        'chunks_processed' => $aiResult['chunks_processed'],
                        'retry_used' => $aiResult['retry_used'] ?? false,
                    ]
                );

                // Stage 5: Parse Response
                try {
                    $parsedResponse = $this->aiParser->parse($aiResult['data']);
                    
                    $this->logger->logSuccess($lesson, 'parsing', 'Response parsed successfully', [
                        'multiple_choice_count' => count($parsedResponse['multiple_choice']),
                        'identification_count' => count($parsedResponse['identification']),
                        'true_or_false_count' => count($parsedResponse['true_or_false']),
                    ]);

                } catch (\Exception $e) {
                    $this->logger->logError($lesson, 'parsing', $e->getMessage());
                    throw $e;
                }

                // Stage 6: Save Assessment
                try {
                    $assessment = $this->assessmentGenerator->generate(
                        $lesson,
                        $parsedResponse,
                        ['title' => "Assessment for {$request->title}"]
                    );

                    $this->logger->logSuccess($lesson, 'saving', 'Assessment saved successfully', [
                        'assessment_id' => $assessment->id,
                        'total_items' => $assessment->items->count(),
                    ]);

                } catch (\Exception $e) {
                    $this->logger->logError($lesson, 'saving', $e->getMessage());
                    throw $e;
                }

            } catch (\Exception $e) {
                $this->logger->logError($lesson, 'ai_call', $e->getMessage());
                throw $e;
            }

            DB::commit();

            return redirect()->route('instructor.lessons.edit', $lesson->id)
                ->with('success', 'Lesson uploaded and assessment generated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->withErrors([
                'error' => 'Failed to process lesson: ' . $e->getMessage(),
            ])->withInput();
        }
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

        $lesson->load(['subject', 'assessments.items']);

        return Inertia::render('Instructor/Lessons/Edit', [
            'lesson' => $lesson,
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

            DB::commit();

            return back()->with('success', 'Assessment updated successfully!');

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
