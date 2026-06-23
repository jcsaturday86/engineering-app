<?php

namespace Tests\Unit\Services;

use App\Enums\ComputationMethod;
use App\Models\FeeCategory;
use App\Models\FeeSchedule;
use App\Models\FeeType;
use App\Models\PermitType;
use App\Services\FeeComputationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeeComputationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FeeComputationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FeeComputationService();
    }

    // ------------------------------------------------------------------
    // Helper: create a FeeType with its required parent records
    // ------------------------------------------------------------------

    private function createFeeType(string $method, array $overrides = []): FeeType
    {
        $permitType = PermitType::create([
            'code' => 'BP',
            'name' => 'Building Permit',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $feeCategory = FeeCategory::create([
            'permit_type_id' => $permitType->id,
            'code' => 'TEST',
            'name' => 'Test Category',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return FeeType::create(array_merge([
            'fee_category_id' => $feeCategory->id,
            'code' => 'TEST-FEE',
            'name' => 'Test Fee',
            'computation_method' => $method,
            'has_excess' => false,
            'has_minimum' => false,
            'has_maximum' => false,
            'is_active' => true,
            'sort_order' => 1,
        ], $overrides));
    }

    // ------------------------------------------------------------------
    // computeFixedFee
    // ------------------------------------------------------------------

    public function test_compute_fixed_fee(): void
    {
        $schedule = new FeeSchedule([
            'fixed_fee' => 500,
            'fee_per_unit' => 0,
            'is_active' => true,
        ]);

        // Fixed fee ignores quantity
        $this->assertEquals(500.0, $this->service->computeFixedFee($schedule, 1));
        $this->assertEquals(500.0, $this->service->computeFixedFee($schedule, 10));
        $this->assertEquals(500.0, $this->service->computeFixedFee($schedule, 100));
        $this->assertEquals(500.0, $this->service->computeFixedFee($schedule, 0));
    }

    // ------------------------------------------------------------------
    // computePerUnitFee
    // ------------------------------------------------------------------

    public function test_compute_per_unit_fee(): void
    {
        $schedule = new FeeSchedule([
            'fee_per_unit' => 25,
            'fixed_fee' => 0,
            'is_active' => true,
        ]);

        $this->assertEquals(250.0, $this->service->computePerUnitFee($schedule, 10));
        $this->assertEquals(0.0, $this->service->computePerUnitFee($schedule, 0));
        $this->assertEquals(25.0, $this->service->computePerUnitFee($schedule, 1));
        $this->assertEquals(125.0, $this->service->computePerUnitFee($schedule, 5));
    }

    public function test_compute_per_unit_fee_with_fractional_quantity(): void
    {
        $schedule = new FeeSchedule([
            'fee_per_unit' => 10,
            'fixed_fee' => 0,
            'is_active' => true,
        ]);

        $this->assertEquals(25.0, $this->service->computePerUnitFee($schedule, 2.5));
    }

    // ------------------------------------------------------------------
    // computeRangeBasedFee (via the public computeFee entry point)
    // ------------------------------------------------------------------

    public function test_compute_range_based_fee(): void
    {
        $feeType = $this->createFeeType(ComputationMethod::RANGE_BASED->value);

        // Range 0-100 => fixed 500
        FeeSchedule::create([
            'fee_type_id' => $feeType->id,
            'range_from' => 0,
            'range_to' => 100,
            'fixed_fee' => 500,
            'fee_per_unit' => 0,
            'is_active' => true,
        ]);

        // Range 101-500 => fixed 1000
        FeeSchedule::create([
            'fee_type_id' => $feeType->id,
            'range_from' => 101,
            'range_to' => 500,
            'fixed_fee' => 1000,
            'fee_per_unit' => 0,
            'is_active' => true,
        ]);

        // Range 501-1000 => fixed 2000
        FeeSchedule::create([
            'fee_type_id' => $feeType->id,
            'range_from' => 501,
            'range_to' => 1000,
            'fixed_fee' => 2000,
            'fee_per_unit' => 0,
            'is_active' => true,
        ]);

        // Test value in first range
        $result = $this->service->computeRangeBasedFee($feeType, 50, null, null);
        $this->assertEquals(500.0, $result);

        // Test value in second range
        $result = $this->service->computeRangeBasedFee($feeType, 200, null, null);
        $this->assertEquals(1000.0, $result);

        // Test value in third range
        $result = $this->service->computeRangeBasedFee($feeType, 750, null, null);
        $this->assertEquals(2000.0, $result);

        // Test boundary: exactly at range_from
        $result = $this->service->computeRangeBasedFee($feeType, 101, null, null);
        $this->assertEquals(1000.0, $result);

        // Test boundary: exactly at range_to
        $result = $this->service->computeRangeBasedFee($feeType, 100, null, null);
        $this->assertEquals(500.0, $result);
    }

    public function test_compute_range_based_fee_returns_zero_for_out_of_range(): void
    {
        $feeType = $this->createFeeType(ComputationMethod::RANGE_BASED->value);

        FeeSchedule::create([
            'fee_type_id' => $feeType->id,
            'range_from' => 0,
            'range_to' => 100,
            'fixed_fee' => 500,
            'fee_per_unit' => 0,
            'is_active' => true,
        ]);

        // Value above all ranges
        $result = $this->service->computeRangeBasedFee($feeType, 200, null, null);
        $this->assertEquals(0.0, $result);
    }

    // ------------------------------------------------------------------
    // computePercentageFee
    // ------------------------------------------------------------------

    public function test_compute_percentage_fee(): void
    {
        $schedule = new FeeSchedule([
            'percentage' => 5, // 5%
            'minimum_fee' => 0,
            'maximum_fee' => 0,
            'is_active' => true,
        ]);

        // 5% of 10000 = 500
        $this->assertEquals(500.0, $this->service->computePercentageFee($schedule, 10000));

        // 5% of 1000 = 50
        $this->assertEquals(50.0, $this->service->computePercentageFee($schedule, 1000));

        // 5% of 0 = 0
        $this->assertEquals(0.0, $this->service->computePercentageFee($schedule, 0));
    }

    public function test_compute_percentage_fee_respects_minimum(): void
    {
        $schedule = new FeeSchedule([
            'percentage' => 1, // 1%
            'minimum_fee' => 100,
            'maximum_fee' => 0,
            'is_active' => true,
        ]);

        // 1% of 500 = 5, but minimum is 100
        $this->assertEquals(100.0, $this->service->computePercentageFee($schedule, 500));
    }

    public function test_compute_percentage_fee_respects_maximum(): void
    {
        $schedule = new FeeSchedule([
            'percentage' => 10, // 10%
            'minimum_fee' => 0,
            'maximum_fee' => 5000,
            'is_active' => true,
        ]);

        // 10% of 100000 = 10000, but maximum is 5000
        $this->assertEquals(5000.0, $this->service->computePercentageFee($schedule, 100000));
    }

    // ------------------------------------------------------------------
    // applyExcess
    // ------------------------------------------------------------------

    public function test_apply_excess(): void
    {
        $schedule = new FeeSchedule([
            'excess_threshold' => 100,
            'excess_fee' => 5,
            'excess_every' => 1,
            'is_active' => true,
        ]);

        // quantity=150, threshold=100, excess=50 units * 5 = 250
        $result = $this->service->applyExcess(0, $schedule, 150);
        $this->assertEquals(250.0, $result);
    }

    public function test_apply_excess_returns_zero_when_below_threshold(): void
    {
        $schedule = new FeeSchedule([
            'excess_threshold' => 100,
            'excess_fee' => 5,
            'excess_every' => 1,
            'is_active' => true,
        ]);

        $result = $this->service->applyExcess(0, $schedule, 50);
        $this->assertEquals(0.0, $result);
    }

    public function test_apply_excess_returns_zero_at_exact_threshold(): void
    {
        $schedule = new FeeSchedule([
            'excess_threshold' => 100,
            'excess_fee' => 5,
            'excess_every' => 1,
            'is_active' => true,
        ]);

        $result = $this->service->applyExcess(0, $schedule, 100);
        $this->assertEquals(0.0, $result);
    }

    public function test_apply_excess_with_excess_every_grouping(): void
    {
        $schedule = new FeeSchedule([
            'excess_threshold' => 20000,
            'excess_fee' => 4,
            'excess_every' => 1000,
            'is_active' => true,
        ]);

        // Excess = 35000 - 20000 = 15000, units = ceil(15000/1000) = 15, fee = 15 * 4 = 60
        $result = $this->service->applyExcess(0, $schedule, 35000);
        $this->assertEquals(60.0, $result);
    }

    public function test_apply_excess_rounds_up_partial_units(): void
    {
        $schedule = new FeeSchedule([
            'excess_threshold' => 100,
            'excess_fee' => 10,
            'excess_every' => 50,
            'is_active' => true,
        ]);

        // Excess = 175 - 100 = 75, units = ceil(75/50) = 2, fee = 2 * 10 = 20
        $result = $this->service->applyExcess(0, $schedule, 175);
        $this->assertEquals(20.0, $result);
    }

    // ------------------------------------------------------------------
    // applyMinMax
    // ------------------------------------------------------------------

    public function test_apply_min_max_enforces_minimum(): void
    {
        $schedule = new FeeSchedule([
            'minimum_fee' => 100,
            'maximum_fee' => 0,
            'is_active' => true,
        ]);

        $this->assertEquals(100.0, $this->service->applyMinMax(50, $schedule));
        $this->assertEquals(200.0, $this->service->applyMinMax(200, $schedule));
    }

    public function test_apply_min_max_enforces_maximum(): void
    {
        $schedule = new FeeSchedule([
            'minimum_fee' => 0,
            'maximum_fee' => 5000,
            'is_active' => true,
        ]);

        $this->assertEquals(5000.0, $this->service->applyMinMax(10000, $schedule));
        $this->assertEquals(3000.0, $this->service->applyMinMax(3000, $schedule));
    }

    public function test_apply_min_max_enforces_both(): void
    {
        $schedule = new FeeSchedule([
            'minimum_fee' => 100,
            'maximum_fee' => 5000,
            'is_active' => true,
        ]);

        $this->assertEquals(100.0, $this->service->applyMinMax(10, $schedule));
        $this->assertEquals(5000.0, $this->service->applyMinMax(10000, $schedule));
        $this->assertEquals(2500.0, $this->service->applyMinMax(2500, $schedule));
    }

    public function test_apply_min_max_no_constraints(): void
    {
        $schedule = new FeeSchedule([
            'minimum_fee' => 0,
            'maximum_fee' => 0,
            'is_active' => true,
        ]);

        $this->assertEquals(42.0, $this->service->applyMinMax(42, $schedule));
    }

    // ------------------------------------------------------------------
    // Full computeFee integration through the service
    // ------------------------------------------------------------------

    public function test_compute_fee_fixed_method(): void
    {
        $feeType = $this->createFeeType(ComputationMethod::FIXED->value);

        FeeSchedule::create([
            'fee_type_id' => $feeType->id,
            'fixed_fee' => 750,
            'fee_per_unit' => 0,
            'is_active' => true,
        ]);

        $dto = $this->service->computeFee($feeType, 1);

        $this->assertEquals(750.0, $dto->amount);
        $this->assertEquals('fixed', $dto->computation_details['method']);
    }

    public function test_compute_fee_per_unit_method(): void
    {
        $feeType = $this->createFeeType(ComputationMethod::PER_UNIT->value);

        FeeSchedule::create([
            'fee_type_id' => $feeType->id,
            'fixed_fee' => 0,
            'fee_per_unit' => 12.50,
            'is_active' => true,
        ]);

        $dto = $this->service->computeFee($feeType, 20);

        $this->assertEquals(250.0, $dto->amount);
        $this->assertEquals('per_unit', $dto->computation_details['method']);
    }

    public function test_compute_fee_with_excess_applied(): void
    {
        $feeType = $this->createFeeType(ComputationMethod::FIXED->value, [
            'has_excess' => true,
        ]);

        FeeSchedule::create([
            'fee_type_id' => $feeType->id,
            'fixed_fee' => 500,
            'fee_per_unit' => 0,
            'excess_threshold' => 100,
            'excess_fee' => 5,
            'excess_every' => 1,
            'is_active' => true,
        ]);

        // Base = 500 (fixed), excess = (150-100)*5 = 250, total = 750
        $dto = $this->service->computeFee($feeType, 150);

        $this->assertEquals(750.0, $dto->amount);
        $this->assertEquals(250.0, $dto->excess_fee);
    }

    public function test_compute_fee_with_min_max_applied(): void
    {
        $feeType = $this->createFeeType(ComputationMethod::PER_UNIT->value, [
            'has_minimum' => true,
            'has_maximum' => true,
        ]);

        FeeSchedule::create([
            'fee_type_id' => $feeType->id,
            'fixed_fee' => 0,
            'fee_per_unit' => 2,
            'minimum_fee' => 100,
            'maximum_fee' => 5000,
            'is_active' => true,
        ]);

        // per_unit = 2 * 10 = 20, but minimum is 100
        $dto = $this->service->computeFee($feeType, 10);
        $this->assertEquals(100.0, $dto->amount);

        // per_unit = 2 * 5000 = 10000, but maximum is 5000
        $dto2 = $this->service->computeFee($feeType, 5000);
        $this->assertEquals(5000.0, $dto2->amount);
    }
}
