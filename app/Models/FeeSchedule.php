<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeSchedule extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'fee_type_id',
        'occupancy_division_id',
        'occupancy_sub_group_id',
        'range_from',
        'range_to',
        'fixed_fee',
        'fee_per_unit',
        'percentage',
        'excess_threshold',
        'excess_fee',
        'excess_every',
        'minimum_fee',
        'maximum_fee',
        'formula',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'range_from' => 'decimal:2',
            'range_to' => 'decimal:2',
            'fixed_fee' => 'decimal:2',
            'fee_per_unit' => 'decimal:4',
            'percentage' => 'decimal:6',
            'excess_threshold' => 'decimal:2',
            'excess_fee' => 'decimal:4',
            'excess_every' => 'decimal:2',
            'minimum_fee' => 'decimal:2',
            'maximum_fee' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Fee type this schedule belongs to.
     */
    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class);
    }

    /**
     * Occupancy division this schedule optionally belongs to.
     */
    public function occupancyDivision(): BelongsTo
    {
        return $this->belongsTo(OccupancyDivision::class);
    }

    /**
     * Occupancy sub-group this schedule optionally belongs to.
     */
    public function occupancySubGroup(): BelongsTo
    {
        return $this->belongsTo(OccupancySubGroup::class);
    }
}
