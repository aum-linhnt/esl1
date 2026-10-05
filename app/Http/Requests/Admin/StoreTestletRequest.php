<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreTestletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() || $this->user()?->isTeacher();
    }

    public function rules(): array
    {
        return [
            'skill' => 'required|in:reading,listening',
            'difficulty' => 'required|in:A1,A2,B1,B2,C1',
            'passage_title' => 'required|string|max:255',
            'passage_content' => 'required|string',
            'audio_url' => 'nullable|string',
            'audio_file' => 'nullable|file|mimes:mp3,wav,ogg,m4a,webm,aac|max:25600',
            'part' => 'nullable|integer|min:1|max:10',
            'questions' => 'required|array|min:1',
            'questions.*.question_text' => 'required|string',
            'questions.*.opt_a' => 'required|string',
            'questions.*.opt_b' => 'required|string',
            'questions.*.opt_c' => 'required|string',
            'questions.*.opt_d' => 'required|string',
            'questions.*.correct_answer' => 'required|in:A,B,C,D',
            'questions.*.explanation' => 'nullable|string',
        ];
    }
}
