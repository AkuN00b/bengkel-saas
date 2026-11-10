<?php

namespace App\Enums;

enum WorkOrderStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft', self::Open => 'Antre', self::InProgress => 'Dikerjakan',
            self::Completed => 'Selesai', self::Cancelled => 'Dibatalkan',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Draft, self::Open, self::InProgress], true);
    }
}
