<?php

namespace App\Services;

use App\DTOs\AssessmentItemDTO;
use App\Enums\ComputationMethod;
use App\Models\FeeSchedule;
use App\Models\FeeType;

class FeeComputationService
{
    /**
     * Compute the fee for a given fee type, quantity, and optional occupancy parameters.
     *
     * This is the main entry point for fee calculation. It determines the computation
     * method and delegates to the appropriate calculator.
     */
    public function computeFee(
        FeeType $feeType,
        float $quantity,
        ?int $divisionId = null,
        ?int $subGroupId = null,
    ): AssessmentItemDTO {
        $method = ComputationMethod::from($feeType->computation_method);

        $schedule = $this->findSchedule($feeType, $divisionId, $subGroupId, $quantity);

        $baseAmount = match ($method) {
            ComputationMethod::FIXED => $this->computeFixedFee($schedule, $quantity),
            ComputationMethod::PER_UNIT => $this->computePerUnitFee($schedule, $quantity),
            ComputationMethod::RANGE_BASED => $this->computeRangeBasedFee($feeType, $quantity, $divisionId, $subGroupId),
            ComputationMethod::CUMULATIVE_RANGE => $this->computeCumulativeRangeFee($feeType, $quantity, $divisionId),
            ComputationMethod::PERCENTAGE => $this->computePercentageFee($schedule, $quantity),
            ComputationMethod::FORMULA => $this->computeFormulaFee($schedule, $quantity),
        };

        // Apply excess if applicable
        $excessAmount = 0;
        if ($feeType->has_excess && $schedule) {
            $excessAmount = $this->applyExcess($baseAmount, $schedule, $quantity);
        }

        $totalAmount = $baseAmount + $excessAmount;

        // Apply min/max if applicable
        if ($schedule && ($feeType->has_minimum || $feeType->has_maximum)) {
            $totalAmount = $this->applyMinMax($totalAmount, $schedule);
        }

        $unitFee = $schedule ? (float) $schedule->fee_per_unit ?: (float) $schedule->fixed_fee : 0;
        $feeCategory = $feeType->feeCategory;

        return new AssessmentItemDTO(
            fee_code: $feeType->code,
            description: $feeType->name,
            quantity: $quantity,
            unit_fee: $unitFee,
            excess_fee: $excessAmount,
            inspection_fee: 0,
            amount: round($totalAmount, 2),
            computation_details: [
                'method' => $method->value,
                'base_amount' => round($baseAmount, 2),
                'excess_amount' => round($excessAmount, 2),
                'schedule_id' => $schedule?->id,
                'division_id' => $divisionId,
                'sub_group_id' => $subGroupId,
            ],
            fee_category_id: $feeCategory?->id,
            fee_type_id: $feeType->id,
        );
    }

    /**
     * Compute a fixed fee. Returns the fixed fee amount regardless of quantity.
     */
    public function computeFixedFee(FeeSchedule $schedule, float $quantity): float
    {
        return (float) $schedule->fixed_fee;
    }

    /**
     * Compute a per-unit fee. Multiplies the unit fee by the quantity.
     */
    public function computePerUnitFee(FeeSchedule $schedule, float $quantity): float
    {
        return (float) $schedule->fee_per_unit * $quantity;
    }

    /**
     * Compute a range-based fee. Finds the range where the value falls
     * and returns the corresponding fixed fee or per-unit fee for that range.
     */
    public function computeRangeBasedFee(
        FeeType $feeType,
        float $value,
        ?int $divisionId,
        ?int $subGroupId,
    ): float {
        $schedule = $this->findScheduleInRange($feeType, $value, $divisionId, $subGroupId);

        if (! $schedule) {
            return 0;
        }

        // If the range has a fixed fee, return it directly
        if ((float) $schedule->fixed_fee > 0) {
            return (float) $schedule->fixed_fee;
        }

        // Otherwise compute per-unit fee within the range
        return (float) $schedule->fee_per_unit * $value;
    }

    /**
     * Compute a cumulative range fee. Sums all completed ranges plus
     * the partial amount for the range where the value falls.
     *
     * Example: If ranges are 0-20000, 20001-50000, 50001-100000 and value is 75000:
     * - Full fee for 0-20000 range
     * - Full fee for 20001-50000 range
     * - Partial fee for 50001-75000 portion of the 50001-100000 range
     */
    public function computeCumulativeRangeFee(
        FeeType $feeType,
        float $value,
        ?int $divisionId,
    ): float {
        $schedules = $feeType->feeSchedules()
            ->where('is_active', true)
            ->when($divisionId, fn ($q) => $q->where('occupancy_division_id', $divisionId))
            ->when(! $divisionId, fn ($q) => $q->whereNull('occupancy_division_id'))
            ->orderBy('range_from')
            ->get();

        $totalFee = 0;
        $remaining = $value;

        foreach ($schedules as $schedule) {
            if ($remaining <= 0) {
                break;
            }

            $rangeFrom = (float) $schedule->range_from;
            $rangeTo = (float) $schedule->range_to;
            $rangeSpan = $rangeTo - $rangeFrom;

            // If value is below the start of this range, stop
            if ($value < $rangeFrom) {
                break;
            }

            // If this range has a fixed fee (base fee for the first bracket)
            if ((float) $schedule->fixed_fee > 0 && $rangeFrom === 0) {
                $totalFee += (float) $schedule->fixed_fee;
                $remaining = $value - $rangeTo;

                continue;
            }

            // Determine how much of this range is applicable
            $applicableAmount = min($remaining, $rangeSpan > 0 ? $rangeSpan : $remaining);

            if ((float) $schedule->fee_per_unit > 0) {
                // Calculate units within this range
                $excessEvery = (float) $schedule->excess_every ?: 1;
                $units = ceil($applicableAmount / $excessEvery);
                $totalFee += $units * (float) $schedule->fee_per_unit;
            } elseif ((float) $schedule->fixed_fee > 0) {
                $totalFee += (float) $schedule->fixed_fee;
            }

            $remaining -= $applicableAmount;
        }

        return $totalFee;
    }

    /**
     * Compute a percentage-based fee. Applies the percentage to the basis value
     * and respects minimum/maximum constraints.
     */
    public function computePercentageFee(FeeSchedule $schedule, float $basisValue): float
    {
        $percentage = (float) $schedule->percentage;
        $amount = $basisValue * ($percentage / 100);

        return $this->applyMinMax($amount, $schedule);
    }

    /**
     * Apply excess fee calculation when quantity exceeds threshold.
     *
     * If quantity exceeds the threshold, the excess portion is charged
     * at the excess rate per excess_every unit increment.
     */
    public function applyExcess(float $baseAmount, FeeSchedule $schedule, float $quantity): float
    {
        $threshold = (float) $schedule->excess_threshold;
        $excessRate = (float) $schedule->excess_fee;
        $excessEvery = (float) $schedule->excess_every ?: 1;

        if ($threshold <= 0 || $excessRate <= 0 || $quantity <= $threshold) {
            return 0;
        }

        $excessQuantity = $quantity - $threshold;
        $units = ceil($excessQuantity / $excessEvery);

        return $units * $excessRate;
    }

    /**
     * Enforce minimum and maximum fee constraints.
     */
    public function applyMinMax(float $amount, FeeSchedule $schedule): float
    {
        $minimum = (float) $schedule->minimum_fee;
        $maximum = (float) $schedule->maximum_fee;

        if ($minimum > 0 && $amount < $minimum) {
            $amount = $minimum;
        }

        if ($maximum > 0 && $amount > $maximum) {
            $amount = $maximum;
        }

        return $amount;
    }

    /**
     * Compute fee using a stored formula (placeholder for custom formula evaluation).
     */
    protected function computeFormulaFee(FeeSchedule $schedule, float $quantity): float
    {
        // Formula evaluation is a placeholder — implement custom formula parsing as needed.
        // For now, fall back to fixed fee if formula is not evaluable.
        return (float) $schedule->fixed_fee;
    }

    /**
     * Find the applicable fee schedule for the given parameters.
     */
    protected function findSchedule(
        FeeType $feeType,
        ?int $divisionId,
        ?int $subGroupId,
        float $quantity,
    ): ?FeeSchedule {
        $method = ComputationMethod::from($feeType->computation_method);

        // For range-based methods, find the schedule matching the quantity range
        if (in_array($method, [ComputationMethod::RANGE_BASED, ComputationMethod::CUMULATIVE_RANGE])) {
            return $this->findScheduleInRange($feeType, $quantity, $divisionId, $subGroupId);
        }

        // For fixed, per_unit, percentage, formula — find the most specific schedule
        return $feeType->feeSchedules()
            ->where('is_active', true)
            ->when($divisionId, fn ($q) => $q->where('occupancy_division_id', $divisionId))
            ->when(! $divisionId, fn ($q) => $q->whereNull('occupancy_division_id'))
            ->when($subGroupId, fn ($q) => $q->where('occupancy_sub_group_id', $subGroupId))
            ->when(! $subGroupId, fn ($q) => $q->whereNull('occupancy_sub_group_id'))
            ->first();
    }

    /**
     * Find the fee schedule whose range contains the given value.
     */
    protected function findScheduleInRange(
        FeeType $feeType,
        float $value,
        ?int $divisionId,
        ?int $subGroupId,
    ): ?FeeSchedule {
        return $feeType->feeSchedules()
            ->where('is_active', true)
            ->where('range_from', '<=', $value)
            ->where(function ($q) use ($value) {
                $q->where('range_to', '>=', $value)
                    ->orWhere('range_to', 0); // 0 means unlimited upper bound
            })
            ->when($divisionId, fn ($q) => $q->where('occupancy_division_id', $divisionId))
            ->when(! $divisionId, fn ($q) => $q->whereNull('occupancy_division_id'))
            ->when($subGroupId, fn ($q) => $q->where('occupancy_sub_group_id', $subGroupId))
            ->when(! $subGroupId, fn ($q) => $q->whereNull('occupancy_sub_group_id'))
            ->first();
    }
}
