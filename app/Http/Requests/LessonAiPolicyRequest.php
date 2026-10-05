<?php

namespace App\Http\Requests;

use App\Models\Lesson;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use TDSoft\AiTutor\Conversations\TeachingPolicy;

class LessonAiPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $lesson = Lesson::where('course_id', $this->route('courseId'))->find($this->route('lessonId'));

        return $lesson && $this->user() && $lesson->canManageAiTutorPolicy($this->user());
    }

    public function rules(): array
    {
        return [
            'ai_answer_policy' => ['required', Rule::in(TeachingPolicy::POLICIES)],
            'ai_teacher_solution_allowed' => 'required|boolean',
            'ai_exam_mode' => 'required|boolean',
        ];
    }
}
