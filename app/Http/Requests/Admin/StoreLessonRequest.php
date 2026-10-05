<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreLessonRequest extends FormRequest
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
            'order' => 'required|integer|min:1',
            'estimated_minutes' => 'required|integer|min:1',
            'unlock_condition_score' => 'required|integer|min:0|max:100',
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
