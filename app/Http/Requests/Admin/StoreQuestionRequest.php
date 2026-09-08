<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'skill' => 'required|in:reading,listening,writing,speaking',
            'difficulty' => 'required|in:A1,A2,B1,B2,C1,Mixed',
            'question_type' => 'required|in:mcq,multiple_select,fill_blank,word_ordering,drag_drop,matching,true_false,audio_listening,pronunciation_speech,essay_writing',
            'question_text' => 'required|string',
            'correct_answer' => 'nullable|string',
            'audio_url' => 'nullable|string|max:1000',
            'audio_file' => 'nullable|file|mimes:mp3,wav,ogg,m4a,webm,aac|max:25600',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:10240',
        ];
    }


    public function messages(): array
    {
        return [
            'skill.required' => 'Vui lòng chọn kỹ năng.',
            'difficulty.required' => 'Vui lòng chọn độ khó.',
            'question_type.required' => 'Vui lòng chọn dạng câu hỏi.',
            'question_text.required' => 'Nội dung câu hỏi là bắt buộc.',
        ];
    }
}
