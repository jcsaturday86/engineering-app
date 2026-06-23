<?php

namespace App\Enums;

enum ComputationMethod: string
{
    case FIXED = 'fixed';
    case PER_UNIT = 'per_unit';
    case RANGE_BASED = 'range_based';
    case CUMULATIVE_RANGE = 'cumulative_range';
    case PERCENTAGE = 'percentage';
    case FORMULA = 'formula';

    /**
     * Get human-readable label for the computation method.
     */
    public function label(): string
    {
        return match ($this) {
            self::FIXED => 'Fixed Fee',
            self::PER_UNIT => 'Per Unit',
            self::RANGE_BASED => 'Range Based',
            self::CUMULATIVE_RANGE => 'Cumulative Range',
            self::PERCENTAGE => 'Percentage',
            self::FORMULA => 'Formula',
        };
    }
}
