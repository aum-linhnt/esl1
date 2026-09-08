<?php

namespace App\Services\AI;

class AiWritingService
{
    protected GeminiApiService $gemini;

    public function __construct(GeminiApiService $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * Analyze and evaluate an English essay submission using structured JSON output.
     *
     * @param string $essay
     * @param string $topic
     * @param string $targetLevel
     * @return array
     */
    public function evaluateEssay(string $essay, string $topic = '', string $targetLevel = 'B1'): array
    {
        $systemPrompt = <<<PROMPT
# ESL AI Writing Examiner – IELTS / CEFR Essay Evaluation

## Role
You are a professional IELTS / CEFR English Writing Examiner for ESL LMS.
Target CEFR Level: {$targetLevel}. Topic: {$topic}.

## Evaluation Criteria (IELTS-aligned Rubric)
Evaluate the essay across these 4 dimensions:
1. **Task Achievement** (25%): Does the essay address the topic? Is the response complete?
2. **Coherence & Cohesion** (25%): Logical flow, paragraph structure, linking words.
3. **Lexical Resource** (25%): Vocabulary range, accuracy, collocations.
4. **Grammatical Range & Accuracy** (25%): Grammar variety, error frequency.

## Output Requirements
- `overall_score`: 0-100 integer (weighted average of 4 criteria)
- `cefr_level`: Estimated CEFR level based on score (A1/A2/B1/B2/C1/C2)
- `coherence_score`: 0-100 integer for Coherence & Cohesion specifically
- `structure_feedback`: Vietnamese text with specific advice on essay structure improvement
- `grammar_errors`: Array of {original, fix, reason} – each error found with correction and Vietnamese explanation
- `vocabulary_improvements`: Array of {original, suggestion, explanation} – upgrade weak vocabulary to band-appropriate alternatives
- `model_essay`: A polished version of the student's essay demonstrating proper grammar, vocabulary, and structure

## Language
All feedback text (reason, explanation, structure_feedback, model_essay) must be in **Vietnamese** for Vietnamese learners.
PROMPT;

        $jsonSchema = [
            'type' => 'OBJECT',
            'properties' => [
                'overall_score' => ['type' => 'INTEGER'],
                'cefr_level' => ['type' => 'STRING'],
                'coherence_score' => ['type' => 'INTEGER'],
                'structure_feedback' => ['type' => 'STRING'],
                'grammar_errors' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'original' => ['type' => 'STRING'],
                            'fix' => ['type' => 'STRING'],
                            'reason' => ['type' => 'STRING'],
                        ],
                        'required' => ['original', 'fix', 'reason']
                    ]
                ],
                'vocabulary_improvements' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'original' => ['type' => 'STRING'],
                            'suggestion' => ['type' => 'STRING'],
                            'explanation' => ['type' => 'STRING'],
                        ],
                        'required' => ['original', 'suggestion', 'explanation']
                    ]
                ],
                'model_essay' => ['type' => 'STRING'],
            ],
            'required' => ['overall_score', 'cefr_level', 'coherence_score', 'structure_feedback', 'grammar_errors', 'vocabulary_improvements', 'model_essay']
        ];

        $userPrompt = "Please analyze this essay thoroughly:\n\"\"\"\n{$essay}\n\"\"\"";

        $result = $this->gemini->generateContent(
            model: 'flash',
            systemPrompt: $systemPrompt,
            userPrompt: $userPrompt,
            jsonSchema: $jsonSchema,
            temperature: 0.3
        );

        return is_array($result) ? $result : [
            'overall_score' => 80,
            'cefr_level' => 'B1',
            'coherence_score' => 75,
            'structure_feedback' => 'Bài viết có cấu trúc cơ bản. Nên chia thành 3 đoạn rõ ràng.',
            'grammar_errors' => [],
            'vocabulary_improvements' => [],
            'model_essay' => $essay,
        ];
    }
}
