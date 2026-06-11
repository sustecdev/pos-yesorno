<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case Served = 'served';
    case Paid = 'paid';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::Preparing => 'Preparing',
            self::Ready => 'Ready',
            self::Served => 'Served',
            self::Paid => 'Paid',
            self::Closed => 'Closed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Sent => 'amber',
            self::Preparing => 'blue',
            self::Ready => 'green',
            self::Served => 'teal',
            self::Paid => 'emerald',
            self::Closed => 'slate',
            self::Cancelled => 'red',
        };
    }
}
