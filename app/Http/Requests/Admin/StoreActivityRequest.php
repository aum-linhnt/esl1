<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'type' => 'required|string',
            'order' => 'required|integer|min:1',
            'estimated_minutes' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'is_visible' => 'nullable|boolean',
            'is_free_trial' => 'nullable|boolean',
            'available_from' => 'nullable|date',
            'available_until' => 'nullable|date',
            'completion_type' => 'nullable|string|in:manual,auto_view,auto_grade,auto_submit',
            'passing_grade' => 'nullable|numeric|min:0|max:100',
            'max_attempts' => 'nullable|integer|min:1|max:99',
            'time_limit_minutes' => 'nullable|integer|min:1|max:600',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Tên hoạt động là bắt buộc.',
            'type.required' => 'Vui lòng chọn loại hoạt động.',
            'order.required' => 'Thứ tự hiển thị là bắt buộc.',
        ];
    }
}
