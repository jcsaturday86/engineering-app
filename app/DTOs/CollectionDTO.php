<?php

namespace App\DTOs;

readonly class CollectionDTO
{
    public function __construct(
        public string $or_number,
        public string $or_date,
        public string $paid_by,
        public float $amount_due,
        public float $amount_received,
        public string $payment_mode = 'cash',
        public ?string $bank_name = null,
        public ?string $check_number = null,
        public ?string $check_date = null,
        public ?string $online_reference = null,
    ) {}

    /**
     * Convert the DTO to an array suitable for model creation.
     */
    public function toArray(): array
    {
        return array_filter(
            get_object_vars($this),
            fn ($value) => $value !== null
        );
    }
}
