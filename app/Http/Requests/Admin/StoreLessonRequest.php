<?php

namespace App\Http\Requests\Admin;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use TDSoft\AiTutor\Conversations\TeachingPolicy;

class StoreLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        $course = Course::find($this->route('courseId'));

        return $course && $this->user() && Lesson::canManageAiTutorForCourse($this->user(), $course);
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'required|integer|min:1',
            'estimated_minutes' => 'required|integer|min:1',
            'unlock_condition_score' => 'required|integer|min:0|max:100',
            'ai_answer_policy' => ['sometimes', 'required', Rule::in(TeachingPolicy::POLICIES)],
            'ai_teacher_solution_allowed' => 'sometimes|required|boolean',
            'ai_exam_mode' => 'sometimes|required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Tên bài học là bắt buộc.',
            'order.required' => 'Thứ tự bài học là bắt buộc.',
            'estimated_minutes.required' => 'Thời gian ước tính là bắt buộc.',
        ];
    }
}
