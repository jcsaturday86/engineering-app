<?php

namespace App\Enums;

enum AssessmentType: string
{
    case BUILDING = 'building';
    case OCCUPANCY = 'occupancy';
    case ZONING = 'zoning';

    /**
     * Get human-readable label for the assessment type.
     */
    public function label(): string
    {
        return match ($this) {
            self::BUILDING => 'Building',
            self::OCCUPANCY => 'Occupancy',
            self::ZONING => 'Zoning',
        };
    }
}
