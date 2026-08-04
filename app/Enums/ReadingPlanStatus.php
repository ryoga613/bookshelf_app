<?php

namespace App\Enums;

enum ReadingPlanStatus: int
{
    case NotCompleted = 0;
    case Completed = 1;

    // 日本語表示用のラベル
    public function label(): string
    {
        return match ($this) {
            self::NotCompleted => '未読',
            self::Completed => '読了',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::NotCompleted => 'bg-gray-200 text-gray-800',
            self::Completed => 'bg-green-100 text-green-800',
        };
    }
}
