<?php

namespace App\Enums;

enum UserStatus: string
{
    case ACTIVE = 'active';
    case BLOCKED = 'blocked';
    case TRIAL_EXPIRED = 'trial_expired';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Đang hoạt động',
            self::BLOCKED => 'Bị khóa',
            self::TRIAL_EXPIRED => 'Hết hạn dùng thử',
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
