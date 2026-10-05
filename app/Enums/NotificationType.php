<?php

namespace App\Enums;

enum NotificationType: string
{
    case SYSTEM = 'system';
    case MESSAGE = 'message';
    case COURSE = 'course';
    case ASSIGNMENT = 'assignment';
    case GRADE = 'grade';
    case ANNOUNCEMENT = 'announcement';

    public function label(): string
    {
        return match ($this) {
            self::SYSTEM => 'Hệ thống',
            self::MESSAGE => 'Tin nhắn',
            self::COURSE => 'Khóa học',
            self::ASSIGNMENT => 'Bài tập',
            self::GRADE => 'Điểm số',
            self::ANNOUNCEMENT => 'Thông báo chung',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::SYSTEM => 'bell',
            self::MESSAGE => 'chat',
            self::COURSE => 'academic-cap',
            self::ASSIGNMENT => 'clipboard-document-list',
            self::GRADE => 'trophy',
            self::ANNOUNCEMENT => 'megaphone',
        };
    }

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
