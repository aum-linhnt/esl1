<?php

namespace TDSoft\AiTutor\Widget;

use Illuminate\View\Component;
use TDSoft\AiTutor\Core\AiException;
use TDSoft\AiTutor\Knowledge\Access;

final class Widget extends Component
{
    public function __construct(public string $courseId, public string $lessonId) {}

    public function shouldRender(): bool
    {
        try {
            $access = app(Access::class);
            $access->module('ai_tutor_core');
            $access->lesson($this->lessonId, $this->courseId);

            return true;
        } catch (AiException) {
            return false;
        }
    }

    public function render(): mixed
    {
        $access = app(Access::class);
        $access->module('ai_tutor_core');

        return view('ai-tutor::widget', [
            'lesson' => $access->lesson($this->lessonId, $this->courseId),
            'actorId' => $access->actors->resolve()->id,
            'settings' => WidgetSettings::resolve(config('ai-tutor.ui', [])),
        ]);
    }
}
