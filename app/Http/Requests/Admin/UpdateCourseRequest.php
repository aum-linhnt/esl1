<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'level' => 'required|string|in:A1,A2,B1,B2,C1,C2',
            'target_audience' => 'nullable|string|max:255',
            'order' => 'required|integer|min:0',
            'thumbnail' => 'nullable|string|max:255',
            'thumbnail_file' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:10240',
            'badge_reward' => 'nullable|string|max:100',

            'enrollment_key' => 'nullable|string|max:100',
            'enrollment_duration_days' => 'nullable|integer|min:1',
            'grading_scale' => 'nullable|string|in:scale_100,scale_10,scale_4,scale_ielts,scale_pass_fail',
            'passing_grade' => 'nullable|numeric|min:0|max:100',
        ];
    }
}
