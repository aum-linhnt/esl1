<?php

return [
    'mode' => env('THEME_DEFAULT_MODE', 'dark'), // 'dark', 'light', 'auto'
    'accent' => env('THEME_DEFAULT_ACCENT', 'indigo'), // 'blue', 'indigo', 'purple', 'emerald', 'amber', 'rose', 'cyan'
    'glow' => env('THEME_ENABLE_GLOW', true),

    'palettes' => [
        'blue' => [
            'key' => 'blue',
            'name' => 'Ocean Blue',
            'primary' => '#3b82f6',
            'hover' => '#2563eb',
            'gradient' => 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)',
            'glow' => 'rgba(59, 130, 246, 0.35)',
            'icon' => '🔵',
        ],
        'indigo' => [
            'key' => 'indigo',
            'name' => 'Electric Indigo',
            'primary' => '#6366f1',
            'hover' => '#4f46e5',
            'gradient' => 'linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)',
            'glow' => 'rgba(99, 102, 241, 0.35)',
            'icon' => '🟣',
        ],
        'purple' => [
            'key' => 'purple',
            'name' => 'Royal Violet',
            'primary' => '#8b5cf6',
            'hover' => '#7c3aed',
            'gradient' => 'linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%)',
            'glow' => 'rgba(139, 92, 246, 0.35)',
            'icon' => '🔮',
        ],
        'emerald' => [
            'key' => 'emerald',
            'name' => 'Emerald Mint',
            'primary' => '#10b981',
            'hover' => '#059669',
            'gradient' => 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
            'glow' => 'rgba(16, 185, 129, 0.35)',
            'icon' => '🟢',
        ],
        'amber' => [
            'key' => 'amber',
            'name' => 'Sunset Amber',
            'primary' => '#f59e0b',
            'hover' => '#d97706',
            'gradient' => 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
            'glow' => 'rgba(245, 158, 11, 0.35)',
            'icon' => '🟠',
        ],
        'rose' => [
            'key' => 'rose',
            'name' => 'Crimson Rose',
            'primary' => '#f43f5e',
            'hover' => '#e11d48',
            'gradient' => 'linear-gradient(135deg, #f43f5e 0%, #e11d48 100%)',
            'glow' => 'rgba(244, 63, 94, 0.35)',
            'icon' => '🔴',
        ],
        'cyan' => [
            'key' => 'cyan',
            'name' => 'Cyber Cyan',
            'primary' => '#06b6d4',
            'hover' => '#0891b2',
            'gradient' => 'linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)',
            'glow' => 'rgba(6, 182, 212, 0.35)',
            'icon' => '🩵',
        ],
    ],
];
