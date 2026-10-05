<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'body' => 'required_without:attachment_file|nullable|string|max:10000',
            'attachment_file' => 'nullable|file|max:20480',
            'attachment_url' => 'nullable|string|max:2048',
            'type' => 'nullable|string|in:text,image,file',
        ];
    }
}
