<?php

namespace App\Enums;

enum PermitTypeCode: string
{
    case BP = 'BP';
    case OP = 'OP';
    case FP = 'FP';
    case EP = 'EP';
    case DP = 'DP';
    case SGP = 'SGP';
    case SP = 'SP';
    case ELP = 'ELP';
    case MP = 'MP';
    case PP = 'PP';
    case ECP = 'ECP';

    /**
     * Get human-readable label for the permit type code.
     */
    public function label(): string
    {
        return match ($this) {
            self::BP => 'Building Permit',
            self::OP => 'Occupancy Permit',
            self::FP => 'Fencing Permit',
            self::EP => 'Electrical Permit',
            self::DP => 'Demolition Permit',
            self::SP => 'Sanitary/Plumbing Permit',
            self::ELP => 'Electronics Permit',
            self::MP => 'Mechanical Permit',
            self::PP => 'Plumbing Permit',
            self::ECP => 'Excavation/Clearing Permit',
        };
    }
}
