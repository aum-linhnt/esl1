<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTestletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() || $this->user()?->isTeacher();
    }

    public function rules(): array
    {
        return [
            'old_passage_title' => 'required|string',
            'passage_title' => 'required|string|max:255',
            'passage_content' => 'required|string',
            'difficulty' => 'nullable|in:A1,A2,B1,B2,C1',
        ];
    }
}
