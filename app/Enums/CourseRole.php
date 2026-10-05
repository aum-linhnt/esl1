<?php

namespace App\Enums;

enum CourseRole: string
{
    case STUDENT = 'student';
    case TEACHER = 'teacher';
    case ASSISTANT = 'assistant';
    case MANAGER = 'manager';

    public function label(): string
    {
        return match ($this) {
            self::STUDENT => 'Học viên',
            self::TEACHER => 'Giảng viên phụ trách',
            self::ASSISTANT => 'Trợ giảng',
            self::MANAGER => 'Quản trị khóa học',
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
