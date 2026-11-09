<?php

namespace App\Features\Todo\Enums;

enum TodoStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING => 'bg-amber-100 text-amber-800 border-amber-300',
            self::IN_PROGRESS => 'bg-blue-100 text-blue-800 border-blue-300',
            self::COMPLETED => 'bg-emerald-100 text-emerald-800 border-emerald-300',
        };
    }
}

