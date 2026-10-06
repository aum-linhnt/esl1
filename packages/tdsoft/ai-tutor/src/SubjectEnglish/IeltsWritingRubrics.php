<?php

namespace TDSoft\AiTutor\SubjectEnglish;

use TDSoft\AiTutor\Assessment\RubricDefinition;
use TDSoft\AiTutor\Assessment\RubricRepository;

final class IeltsWritingRubrics
{
    public function provision(RubricRepository $repository): array
    {
        $result = [];
        foreach (['ielts_task_1', 'ielts_task_2'] as $task) {
            $descriptions = [
                $task === 'ielts_task_1' ? 'task_achievement' : 'task_response' => $task === 'ielts_task_1'
                    ? 'Task Achievement: judge coverage of the supplied requirements, overview, selected key features, comparisons and accurate supporting data for Academic; purpose, bullet coverage and tone for a clearly identified General Training letter. Do not invent missing source data.'
                    : 'Task Response: judge coverage of all parts of the question, a sustained clear position, relevant main ideas and their development with reasons and examples.',
                'coherence' => 'Coherence & Cohesion: judge progression, paragraph organisation, relationships between ideas, referencing and natural use of linking devices; more connectors alone do not mean a higher band.',
                'vocabulary' => 'Lexical Resource: judge range, precision, appropriateness, collocation, spelling and word formation; assess the effect of errors on communication.',
                'grammar' => 'Grammatical Range & Accuracy: judge structural variety, complex sentence control, error-free sentences, punctuation and the effect of errors on understanding.',
            ];
            $criteria = [];
            foreach ($descriptions as $key => $description) {
                $criteria[$key] = ['weight' => 25, 'description' => $description, 'evidence_type' => 'text'];
            }
            $result[$task] = $repository->provision('english_'.$task, 'writing', $task, 'ielts-band-v1', new RubricDefinition($criteria));
        }

        return $result;
    }
}
