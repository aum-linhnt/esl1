<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() || $this->user()?->isTeacher();
    }

    public function rules(): array
    {
        return [
            'difficulty' => 'required|in:A1,A2,B1,B2,C1,Mixed',
            'question_text' => 'required|string',
            'correct_answer' => 'required|string',
            'explanation' => 'nullable|string',
            'options' => 'nullable|string',
            'audio_url' => 'nullable|string',
            'audio_file' => 'nullable|file|mimes:mp3,wav,ogg,m4a,webm,aac|max:25600',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:10240',
            'passage_title' => 'nullable|string|max:255',
            'passage_content' => 'nullable|string',
            'sync_passage_to_cluster' => 'nullable|boolean',
            'create_new_version' => 'nullable|boolean',
        ];
    }
}
