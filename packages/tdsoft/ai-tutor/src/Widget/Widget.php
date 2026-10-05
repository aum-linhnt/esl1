<?php

namespace TDSoft\AiTutor\Widget;

use Illuminate\View\Component;
use TDSoft\AiTutor\Core\AiException;
use TDSoft\AiTutor\Knowledge\Access;

final class Widget extends Component
{
    public function __construct(public string $courseId, public string $lessonId, public bool $embedded = false) {}

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

        $actorId = $access->actors->resolve()->id;
        $creditLabel = 'Chưa có credit';
        if ($this->embedded) {
            $account = \Illuminate\Support\Facades\DB::table('tutor_ai_credit_accounts')
                ->where(['owner_type' => 'learner', 'owner_id' => $actorId, 'scope' => 'system'])->first();
            if ($account) {
                $creditLabel = $account->status !== 'active' ? 'Credit tạm khóa'
                    : ($account->balance === null ? 'Credit không giới hạn' : 'Còn '.number_format($account->balance).' credit');
            }
        }

        return view($this->embedded ? 'ai-tutor::lesson-panel' : 'ai-tutor::widget', [
            'creditLabel' => $creditLabel,
            'lesson' => $access->lesson($this->lessonId, $this->courseId),
            'actorId' => $access->actors->resolve()->id,
            'settings' => WidgetSettings::resolve(config('ai-tutor.ui', [])),
        ]);
    }
}
