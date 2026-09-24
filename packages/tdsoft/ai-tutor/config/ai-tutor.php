<?php

return [
    // Enable only after an entitlement implementation and real provider are bound.
    'enabled' => (bool) env('AI_TUTOR_ENABLED', false),
    'billing_mode' => env('AI_BILLING_MODE', 'customer_key'),
    'provider' => env('AI_DEFAULT_PROVIDER', 'openai'),
    'model' => env('AI_DEFAULT_CHAT_MODEL', ''),
    'daily_credits' => (int) env('AI_DAILY_CREDITS', 10),
    'credentials' => [
        'openai' => env('OPENAI_API_KEY'),
        'gemini' => env('GEMINI_API_KEY'),
    ],
    'providers' => [],
    'features' => [
        'tutor_message' => 'ai_tutor_core',
        'tutor_image_question' => 'ai_tutor_core',
        'speaking_transcription' => 'ai_tutor_speaking',
        'speaking_assessment' => 'ai_tutor_speaking',
        'writing_assessment' => 'ai_tutor_writing',
        'writing_recheck' => 'ai_tutor_writing',
        'knowledge_embedding' => 'ai_tutor_knowledge',
        'exercise_generation' => 'ai_tutor_core',
        'tts_playback' => 'ai_tutor_core',
    ],
    'theme' => ['default' => 'system', 'allow_user_switch' => true],
];
