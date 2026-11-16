<?php

namespace App\Enums;

enum ReadingPlanStatus: int
{
    case NotCompleted = 0;
    case Reading = 1;
    case Completed = 2;
    case Expired = 3;

    /**
     * 進行中（未読・読書中）として扱うステータスの値
     */
    public static function inProgressValues(): array
    {
        return [self::NotCompleted->value, self::Reading->value];
    }

    // 日本語表示用のラベル
    public function label(): string
    {
        return match ($this) {
            self::NotCompleted => '未読',
            self::Reading => '読書中',
            self::Completed => '読了',
            self::Expired => '期限切れ',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::NotCompleted => 'bg-gray-200 text-gray-800',
            self::Reading => 'bg-blue-100 text-blue-800',
            self::Completed => 'bg-green-100 text-green-800',
            self::Expired => 'bg-red-100 text-red-800',
        };
    }
}
