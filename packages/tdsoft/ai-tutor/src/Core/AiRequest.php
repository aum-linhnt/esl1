<?php

namespace TDSoft\AiTutor\Core;

final readonly class AiRequest
{
    public function __construct(
        public string $feature,
        public LearnerIdentity $actor,
        public array $payload,
        public string $requestId,
        public string $idempotencyKey,
        public ?string $courseId = null,
        public ?string $lessonId = null,
        public ?string $questionId = null,
        public LessonContext|QuestionContext|null $context = null,
    ) {
        if (! preg_match('/^(?:[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}|[0-9A-HJKMNP-TV-Z]{26})$/i', $requestId)
            || $idempotencyKey === '' || strlen($idempotencyKey) > 191
            || ($questionId !== null && $lessonId === null)
            || ($courseId !== null && $lessonId === null)) {
            throw new AiException('AI_REQUEST_INVALID');
        }
    }

    public static function forFeature(
        string $feature, LearnerIdentity $actor, array $payload, string $requestId, string $idempotencyKey,
        ?string $courseId = null, ?string $lessonId = null, ?string $questionId = null,
    ): self {
        return new self($feature, $actor, $payload, $requestId, $idempotencyKey, $courseId, $lessonId, $questionId);
    }

    public function withContext(LessonContext|QuestionContext|null $context): self
    {
        return new self($this->feature, $this->actor, $this->payload, $this->requestId, $this->idempotencyKey,
            $this->courseId, $this->lessonId, $this->questionId, $context);
    }

    public function fingerprint(): string
    {
        $canonicalize = function (array $value) use (&$canonicalize): array {
            if (! array_is_list($value)) {
                ksort($value);
            }
            foreach ($value as &$item) {
                if (is_array($item)) {
                    $item = $canonicalize($item);
                }
            }
            return $value;
        };

        return hash('sha256', json_encode($canonicalize([
            'actor' => $this->actor->id, 'feature' => $this->feature, 'payload' => $this->payload,
            'course' => $this->courseId, 'lesson' => $this->lessonId, 'question' => $this->questionId,
        ]), JSON_THROW_ON_ERROR));
    }
}
