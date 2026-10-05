<?php

namespace TDSoft\AiTutor\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use TDSoft\AiTutor\Knowledge\CourseSyncService;
use TDSoft\AiTutor\Knowledge\ProcessKnowledgeVersion;

final class CourseSyncController
{
    public function courses(CourseSyncService $sync): JsonResponse
    {
        return new JsonResponse($sync->courses());
    }

    public function preview(Request $request, CourseSyncService $sync): JsonResponse
    {
        $data = $request->validate(['course_id' => 'required|string|max:191']);

        return new JsonResponse($sync->preview($data['course_id']));
    }

    public function store(Request $request, CourseSyncService $sync): JsonResponse
    {
        $data = $request->validate([
            'course_id' => 'required|string|max:191',
            'lessons' => 'required|array|min:1|max:50',
            'lessons.*.lesson_id' => 'required|string|max:191|distinct',
            'lessons.*.fingerprint' => 'required|string|size:64',
            'enqueue' => 'required|boolean',
        ]);
        $results = $sync->sync($data['course_id'], $data['lessons']);
        if ($data['enqueue']) {
            foreach ($results as $result) {
                // Repeated dispatch uses existing version/chunk request IDs; never creates new paid IDs.
                ProcessKnowledgeVersion::dispatch($result['version_id'])->afterCommit();
            }
        }

        return new JsonResponse(['results' => $results, 'queued' => $data['enqueue']]);
    }
}
