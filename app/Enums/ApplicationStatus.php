<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case ZONING_ASSESSED = 'zoning_assessed';
    case ENGINEERING_ASSESSED = 'engineering_assessed';
    case BILLED = 'billed';
    case PAID = 'paid';
    case PERMIT_GENERATED = 'permit_generated';
    case RELEASED = 'released';
    case CANCELLED = 'cancelled';

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Submitted',
            self::ZONING_ASSESSED => 'Zoning Assessed',
            self::ENGINEERING_ASSESSED => 'Engineering Assessed',
            self::BILLED => 'Billed',
            self::PAID => 'Paid',
            self::PERMIT_GENERATED => 'Permit Generated',
            self::RELEASED => 'Released',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Get Tailwind CSS color classes for the status badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-gray-100 text-gray-800',
            self::SUBMITTED => 'bg-blue-100 text-blue-800',
            self::ZONING_ASSESSED => 'bg-yellow-100 text-yellow-800',
            self::ENGINEERING_ASSESSED => 'bg-yellow-100 text-yellow-800',
            self::BILLED => 'bg-orange-100 text-orange-800',
            self::PAID => 'bg-green-100 text-green-800',
            self::PERMIT_GENERATED => 'bg-indigo-100 text-indigo-800',
            self::RELEASED => 'bg-emerald-100 text-emerald-800',
            self::CANCELLED => 'bg-red-100 text-red-800',
        };
    }

    /**
     * Get the allowed transitions from each status.
     *
     * @return array<string, list<self>>
     */
    public static function allowedTransitions(): array
    {
        return [
            self::DRAFT->value => [self::SUBMITTED, self::CANCELLED],
            self::SUBMITTED->value => [self::ZONING_ASSESSED, self::ENGINEERING_ASSESSED, self::CANCELLED],
            self::ZONING_ASSESSED->value => [self::ENGINEERING_ASSESSED, self::CANCELLED],
            self::ENGINEERING_ASSESSED->value => [self::BILLED, self::CANCELLED],
            self::BILLED->value => [self::PAID, self::CANCELLED],
            self::PAID->value => [self::PERMIT_GENERATED],
            self::PERMIT_GENERATED->value => [self::RELEASED],
            self::RELEASED->value => [],
            self::CANCELLED->value => [],
        ];
    }

    /**
     * Check if a transition to the given status is allowed.
     */
    public function canTransitionTo(self $newStatus): bool
    {
        $allowed = self::allowedTransitions()[$this->value] ?? [];

        return in_array($newStatus, $allowed, true);
    }
}
