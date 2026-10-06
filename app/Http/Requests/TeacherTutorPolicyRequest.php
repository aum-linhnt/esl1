<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class TeacherTutorPolicyRequest extends LessonAiPolicyRequest
{
    protected $errorBag = 'tutorPolicy';

    public function authorize(): bool
    {
        return $this->user()?->isActive() && parent::authorize();
    }

    public function rules(): array
    {
        return [...parent::rules(), 'days' => ['sometimes', 'integer', Rule::in([7, 30, 90])]];
    }

    protected function getRedirectUrl(): string
    {
        $days = $this->input('days');
        return route('teacher.ai-tutor.index', [
            'course_id' => $this->route('courseId'), 'lesson_id' => $this->route('lessonId'),
            'days' => is_scalar($days) && in_array((string) $days, ['7', '30', '90'], true) ? (int) $days : 30,
        ]).'#course-config';
    }
}
