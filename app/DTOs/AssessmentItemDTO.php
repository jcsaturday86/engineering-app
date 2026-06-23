<?php

namespace App\DTOs;

readonly class AssessmentItemDTO
{
    public function __construct(
        public string $fee_code,
        public string $description,
        public float $quantity,
        public float $unit_fee,
        public float $excess_fee,
        public float $inspection_fee,
        public float $amount,
        public ?array $computation_details = null,
        public ?int $fee_category_id = null,
        public ?int $fee_type_id = null,
    ) {}

    /**
     * Convert the DTO to an array suitable for model creation.
     */
    public function toArray(): array
    {
        return [
            'fee_code' => $this->fee_code,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit_fee' => $this->unit_fee,
            'excess_fee' => $this->excess_fee,
            'inspection_fee' => $this->inspection_fee,
            'amount' => $this->amount,
            'computation_details' => $this->computation_details,
            'fee_category_id' => $this->fee_category_id,
            'fee_type_id' => $this->fee_type_id,
        ];
    }
}
