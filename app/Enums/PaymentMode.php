<?php

namespace App\Enums;

enum PaymentMode: string
{
    case CASH = 'cash';
    case CHECK = 'check';
    case ONLINE = 'online';

    /**
     * Get human-readable label for the payment mode.
     */
    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::CHECK => 'Check',
            self::ONLINE => 'Online',
        };
    }
}
