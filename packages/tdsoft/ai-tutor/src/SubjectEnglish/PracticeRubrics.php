<?php

namespace TDSoft\AiTutor\SubjectEnglish;

use TDSoft\AiTutor\Assessment\RubricDefinition;
use TDSoft\AiTutor\Assessment\RubricRepository;

final class PracticeRubrics
{
    /** Explicit provisioning, no credit funding or provider call. */
    public function provision(RubricRepository $repository): array
    {
        $result = [];
        foreach (EnglishProfile::TASKS as $skill => $tasks) {
            foreach ($tasks as $task) {
                $descriptions = $skill === 'writing'
                    ? ['task_response' => 'Addresses the task and supports the response.',
                        'coherence' => 'Organizes ideas with clear connections.',
                        'vocabulary' => 'Uses vocabulary accurately and appropriately.',
                        'grammar' => 'Uses grammatical structures accurately.']
                    : ['pronunciation' => 'Speech intelligibility supported by acoustic evidence.',
                        'fluency' => 'Flow and pauses supported by audio timing evidence.',
                        'vocabulary' => 'Uses vocabulary accurately and appropriately.',
                        'grammar' => 'Uses grammatical structures accurately.'];
                $criteria = [];
                foreach ($descriptions as $key => $description) {
                    $criteria[$key] = ['weight' => 25, 'description' => $description,
                        'evidence_type' => match ($key) {
                            'pronunciation' => 'acoustic', 'fluency' => 'timing', default => 'text'
                        }];
                }
                $result[$task] = $repository->provision('english_'.$task, $skill, $task, 'english-practice-v1', new RubricDefinition($criteria));
            }
        }

        return $result;
    }
}
