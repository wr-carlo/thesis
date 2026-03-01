<?php

namespace App\Services\AI;

class BloomsTaxonomyConfig
{
    /**
     * Complete Bloom's Taxonomy level definitions.
     */
    protected static array $levels = [
        'remember' => [
            'label' => 'Remember',
            'order' => 1,
            'description' => 'Recall of facts, terms, basic concepts, and answers without necessarily understanding their meaning.',
            'action_verbs' => ['define', 'list', 'identify', 'name', 'recall', 'state', 'label', 'recognize', 'match', 'memorize'],
            'mc_guideline' => 'Test direct recall from the content. Distractors should be clearly distinguishable. The correct answer should appear directly in the lesson material.',
            'id_guideline' => 'Ask for specific terms, names, definitions, or facts that appear verbatim or nearly verbatim in the content.',
            'tf_guideline' => 'Use simple, straightforward factual statements directly from the material. Avoid tricky wording.',
        ],
        'understand' => [
            'label' => 'Understand',
            'order' => 2,
            'description' => 'Demonstrating comprehension by organizing, comparing, translating, interpreting, or summarizing information.',
            'action_verbs' => ['explain', 'describe', 'summarize', 'paraphrase', 'classify', 'discuss', 'interpret', 'compare', 'distinguish', 'illustrate'],
            'mc_guideline' => 'Test if the student can interpret meaning, not just memorize. Options should require understanding of concepts rather than rote recall.',
            'id_guideline' => 'Ask the student to explain concepts in context or to provide summaries or interpretations of ideas from the content.',
            'tf_guideline' => 'Use paraphrased statements that test comprehension of ideas. The student must understand the concept to determine truth.',
        ],
        'apply' => [
            'label' => 'Apply',
            'order' => 3,
            'description' => 'Using acquired knowledge to solve problems in new or unfamiliar situations.',
            'action_verbs' => ['apply', 'demonstrate', 'solve', 'use', 'implement', 'execute', 'carry out', 'calculate', 'show', 'predict'],
            'mc_guideline' => 'Present a new scenario or problem that requires using concepts from the lesson. The student must apply what they learned to choose the correct approach.',
            'id_guideline' => 'Describe a situation and ask the student to identify the correct method, principle, or solution to apply.',
            'tf_guideline' => 'Present statements about applying concepts to new situations. The student must understand whether the application is correct.',
        ],
        'analyze' => [
            'label' => 'Analyze',
            'order' => 4,
            'description' => 'Breaking down information into parts to examine relationships, differentiate, organize, or attribute.',
            'action_verbs' => ['analyze', 'compare', 'contrast', 'differentiate', 'examine', 'categorize', 'investigate', 'distinguish', 'relate', 'organize'],
            'mc_guideline' => 'Ask questions that require breaking down concepts, comparing multiple ideas, or identifying cause-and-effect relationships from the content.',
            'id_guideline' => 'Ask the student to identify relationships, differences, patterns, or organizational structures within the content.',
            'tf_guideline' => 'Present statements about relationships, comparisons, or cause-and-effect that require analytical thinking to verify.',
        ],
        'evaluate' => [
            'label' => 'Evaluate',
            'order' => 5,
            'description' => 'Making judgments based on criteria and standards through checking, critiquing, or defending.',
            'action_verbs' => ['evaluate', 'judge', 'justify', 'defend', 'critique', 'assess', 'argue', 'support', 'prioritize', 'recommend'],
            'mc_guideline' => 'Present scenarios requiring judgment or assessment. Options should represent different evaluative perspectives or criteria-based decisions.',
            'id_guideline' => 'Ask the student to justify a position, assess the validity of an argument, or prioritize approaches based on criteria.',
            'tf_guideline' => 'Present evaluative claims or judgments that require the student to assess their validity based on evidence from the content.',
        ],
        'create' => [
            'label' => 'Create',
            'order' => 6,
            'description' => 'Compiling information to generate new solutions, products, perspectives, or designs.',
            'action_verbs' => ['create', 'design', 'construct', 'develop', 'formulate', 'propose', 'devise', 'compose', 'generate', 'plan'],
            'mc_guideline' => 'Present questions about designing solutions, formulating plans, or generating new combinations of ideas from the lesson content.',
            'id_guideline' => 'Ask the student to propose a new approach, formulate a hypothesis, or describe how they would design or construct something using the concepts.',
            'tf_guideline' => 'Present statements about novel applications or creative combinations of concepts that require synthesis-level thinking.',
        ],
    ];

    /**
     * Get all valid Bloom's level keys.
     */
    public static function getValidLevels(): array
    {
        return array_keys(static::$levels);
    }

    /**
     * Get info for a specific level.
     */
    public static function getLevelInfo(string $level): ?array
    {
        return static::$levels[$level] ?? null;
    }

    /**
     * Get all levels with their labels (for frontend).
     */
    public static function getAllLevelsForDisplay(): array
    {
        $result = [];
        foreach (static::$levels as $key => $level) {
            $result[] = [
                'value' => $key,
                'label' => $level['label'],
                'description' => $level['description'],
                'order' => $level['order'],
            ];
        }
        return $result;
    }

    /**
     * Build prompt instructions based on question distribution.
     */
    public static function getPromptInstructions(array $questionDistribution): string
    {
        $validLevels = static::getValidLevels();
        $selectedLevels = array_keys($questionDistribution);
        $selected = array_intersect($selectedLevels, $validLevels);

        if (empty($selected)) {
            $selected = ['remember', 'understand'];
        }

        $selectedLabels = array_map(fn($l) => static::$levels[$l]['label'], $selected);
        $excludedLevels = array_diff($validLevels, $selected);
        $excludedLabels = array_map(fn($l) => static::$levels[$l]['label'], $excludedLevels);

        $prompt = "\n🎯 Bloom's Taxonomy Cognitive Levels Required: " . implode(', ', $selectedLabels) . "\n\n";
        $prompt .= "IMPORTANT INSTRUCTIONS FOR QUESTION GENERATION EXACT AMOUNTS:\n\n";

        foreach ($selected as $level) {
            $info = static::$levels[$level];
            $verbs = implode(', ', $info['action_verbs']);
            
            $counts = $questionDistribution[$level] ?? ['mcq' => 0, 'identification' => 0, 'tf' => 0];
            $mcq = $counts['mcq'] ?? 0;
            $id = $counts['identification'] ?? 0;
            $tf = $counts['tf'] ?? 0;
            
            if ($mcq == 0 && $id == 0 && $tf == 0) continue;

            $prompt .= "Level {$info['order']} - {$info['label']} ({$info['description']}):\n";
            $prompt .= "  • Must generate exactly {$mcq} Multiple Choice questions.\n";
            $prompt .= "  • Must generate exactly {$id} Identification questions.\n";
            $prompt .= "  • Must generate exactly {$tf} True/False questions.\n";
            $prompt .= "  • Use these action verbs to frame questions: {$verbs}\n";
            if ($mcq > 0) $prompt .= "  • Multiple Choice Guideline: {$info['mc_guideline']}\n";
            if ($id > 0) $prompt .= "  • Identification Guideline: {$info['id_guideline']}\n";
            if ($tf > 0) $prompt .= "  • True/False Guideline: {$info['tf_guideline']}\n\n";
        }

        if (!empty($excludedLevels)) {
            $prompt .= "⚠️ DO NOT generate questions at these levels: " . implode(', ', $excludedLabels) . "\n\n";
        }

        $prompt .= "⚠️ Each question MUST include a \"bloom_level\" field indicating its cognitive level.\n";
        $prompt .= "   Valid values for bloom_level: " . implode(', ', $selected) . "\n";
        $prompt .= "   Ensure that you generate EXACTLY the requested amount for each question type per level.\n\n";

        return $prompt;
    }

    /**
     * Get the JSON structure example including bloom_level field.
     */
    public static function getJsonStructure(): string
    {
        $structure = "{\n";
        $structure .= '  "multiple_choice": [' . "\n";
        $structure .= '    {' . "\n";
        $structure .= '      "question": "Question text here",' . "\n";
        $structure .= '      "choices": ["Choice A", "Choice B", "Choice C", "Choice D"],' . "\n";
        $structure .= '      "correct_answer": "Choice A",' . "\n";
        $structure .= '      "bloom_level": "remember"' . "\n";
        $structure .= '    }' . "\n";
        $structure .= '  ],' . "\n";
        $structure .= '  "identification": [' . "\n";
        $structure .= '    {' . "\n";
        $structure .= '      "question": "Question text here",' . "\n";
        $structure .= '      "correct_answer": "Answer text",' . "\n";
        $structure .= '      "bloom_level": "understand"' . "\n";
        $structure .= '    }' . "\n";
        $structure .= '  ],' . "\n";
        $structure .= '  "true_or_false": [' . "\n";
        $structure .= '    {' . "\n";
        $structure .= '      "question": "Statement text here",' . "\n";
        $structure .= '      "correct_answer": "True",' . "\n";
        $structure .= '      "bloom_level": "remember"' . "\n";
        $structure .= '    }' . "\n";
        $structure .= '  ]' . "\n";
        $structure .= "}\n";

        return $structure;
    }
}
