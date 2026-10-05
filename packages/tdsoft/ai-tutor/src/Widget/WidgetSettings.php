<?php

namespace TDSoft\AiTutor\Widget;

use TDSoft\AiTutor\Core\AiException;

final class WidgetSettings
{
    public static function resolve(array $config): array
    {
        $position = $config['launcher_position'] ?? 'bottom-right';
        $mode = $config['lesson_chat_mode'] ?? 'drawer';
        $width = filter_var($config['desktop_panel_width'] ?? 420, FILTER_VALIDATE_INT);
        if (! in_array($position, ['bottom-right', 'bottom-left'], true)
            || ! in_array($mode, ['drawer', 'floating_panel', 'full_page'], true)
            || $width === false || $width < 320 || $width > 640) {
            throw new AiException('AI_WIDGET_CONFIG_INVALID');
        }

        return ['position' => $position, 'mode' => $mode, 'width' => $width,
            'expand' => (bool) ($config['allow_expand_to_page'] ?? true)];
    }
}
