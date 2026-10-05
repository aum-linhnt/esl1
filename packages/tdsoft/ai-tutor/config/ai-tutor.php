<?php

use TDSoft\AiTutor\Providers\OpenAiProvider;

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
    'providers' => ['openai' => OpenAiProvider::class],
    'embedding_model' => env('AI_DEFAULT_EMBEDDING_MODEL', ''),
    'knowledge' => [
        'min_similarity' => 0.25,
        'top_k' => 5,
    ],
    'tutor' => [
        'max_output_tokens' => (int) env('AI_TUTOR_MAX_OUTPUT_TOKENS', 4000),
        'reasoning_effort' => env('AI_TUTOR_REASONING_EFFORT', 'low'),
    ],
    'retention' => [
        'enabled' => (bool) env('AI_CONVERSATION_RETENTION_ENABLED', false),
        'conversation_days' => (int) env('AI_CONVERSATION_RETENTION_DAYS', 365),
    ],
    'ui' => [
        'asset_entries' => [],
        'launcher_position' => env('AI_TUTOR_LAUNCHER_POSITION', 'bottom-right'),
        'lesson_chat_mode' => env('AI_TUTOR_LESSON_CHAT_MODE', 'drawer'),
        'desktop_panel_width' => (int) env('AI_TUTOR_DESKTOP_PANEL_WIDTH', 420),
        'allow_expand_to_page' => (bool) env('AI_TUTOR_ALLOW_EXPAND_TO_PAGE', true),
    ],
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
    'license' => [
        'mode' => env('AI_LICENSE_MODE', 'server'),
        'source_modules' => array_values(array_filter(array_map('trim', explode(',', (string) env('AI_LICENSE_SOURCE_MODULES', ''))), fn ($module) => $module !== '')),
        'server_url' => env('AI_LICENSE_SERVER_URL', ''),
        'key' => env('AI_LICENSE_KEY', ''),
        'installation_id' => env('AI_LICENSE_INSTALLATION_ID', ''),
        'domain' => env('AI_LICENSE_DOMAIN', ''),
        'public_keys' => [
            env('AI_LICENSE_PUBLIC_KEY_ID', 'license-key-2026-01') => env('AI_LICENSE_PUBLIC_KEY_PATH', ''),
        ],
        'refresh_hours' => (int) env('AI_LICENSE_REFRESH_HOURS', 24),
        'grace_days' => (int) env('AI_LICENSE_GRACE_DAYS', 7),
        'admin_middleware' => ['web', 'auth'],
        'admin_layout' => 'ai-tutor::layouts.admin',
        'admin_section' => 'content',
        'admin_theme' => null,
        'asset_entries' => [],
    ],
];
