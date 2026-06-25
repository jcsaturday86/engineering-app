<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case FOR_ZONING_ASSESSMENT = 'for_zoning_assessment';
    case ZONING_ASSESSED = 'zoning_assessed';
    case ENGINEERING_ASSESSED = 'engineering_assessed';
    case BILLED = 'billed';
    case PAID = 'paid';
    case PERMIT_GENERATED = 'permit_generated';
    case RELEASED = 'released';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Submitted',
            self::FOR_ZONING_ASSESSMENT => 'For Zoning Assessment',
            self::ZONING_ASSESSED => 'Zoning Assessed',
            self::ENGINEERING_ASSESSED => 'Engineering Assessed',
            self::BILLED => 'Billed',
            self::PAID => 'Paid',
            self::PERMIT_GENERATED => 'Permit Generated',
            self::RELEASED => 'Released',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-gray-100 text-gray-800',
            self::SUBMITTED => 'bg-blue-100 text-blue-800',
            self::FOR_ZONING_ASSESSMENT => 'bg-purple-100 text-purple-800',
            self::ZONING_ASSESSED => 'bg-yellow-100 text-yellow-800',
            self::ENGINEERING_ASSESSED => 'bg-yellow-100 text-yellow-800',
            self::BILLED => 'bg-orange-100 text-orange-800',
            self::PAID => 'bg-green-100 text-green-800',
            self::PERMIT_GENERATED => 'bg-indigo-100 text-indigo-800',
            self::RELEASED => 'bg-emerald-100 text-emerald-800',
            self::CANCELLED => 'bg-red-100 text-red-800',
        };
    }

    public static function allowedTransitions(): array
    {
        return [
            self::DRAFT->value => [self::SUBMITTED, self::FOR_ZONING_ASSESSMENT, self::CANCELLED],
            self::SUBMITTED->value => [self::ENGINEERING_ASSESSED, self::CANCELLED],
            self::FOR_ZONING_ASSESSMENT->value => [self::ZONING_ASSESSED, self::CANCELLED],
            self::ZONING_ASSESSED->value => [self::ENGINEERING_ASSESSED, self::CANCELLED],
            self::ENGINEERING_ASSESSED->value => [self::BILLED, self::CANCELLED],
            self::BILLED->value => [self::PAID, self::CANCELLED],
            self::PAID->value => [self::PERMIT_GENERATED],
            self::PERMIT_GENERATED->value => [self::RELEASED],
            self::RELEASED->value => [],
            self::CANCELLED->value => [],
        ];
    }

    public static function allowedTransitionsFor(string $permitTypeCode): array
    {
        if ($permitTypeCode === 'OP') {
            return [
                self::DRAFT->value => [self::SUBMITTED, self::CANCELLED],
                self::SUBMITTED->value => [self::ENGINEERING_ASSESSED, self::CANCELLED],
                self::ENGINEERING_ASSESSED->value => [self::BILLED, self::CANCELLED],
                self::BILLED->value => [self::PAID, self::CANCELLED],
                self::PAID->value => [self::PERMIT_GENERATED],
                self::PERMIT_GENERATED->value => [self::RELEASED],
                self::RELEASED->value => [],
                self::CANCELLED->value => [],
            ];
        }

        return self::allowedTransitions();
    }

    public function canTransitionTo(self $newStatus): bool
    {
        $allowed = self::allowedTransitions()[$this->value] ?? [];

        return in_array($newStatus, $allowed, true);
    }

    public function canTransitionToFor(self $newStatus, string $permitTypeCode): bool
    {
        $allowed = self::allowedTransitionsFor($permitTypeCode)[$this->value] ?? [];

        return in_array($newStatus, $allowed, true);
    }
}
