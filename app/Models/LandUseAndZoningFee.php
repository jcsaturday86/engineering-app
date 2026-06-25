<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandUseAndZoningFee extends Model
{
    protected $table = 'land_use_and_zoning_fees';

    protected $fillable = [
        'occupancy_sub_group_id',
        'range_from',
        'range_to',
        'amount',
        'excess_of',
        'percentage',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'range_from' => 'decimal:2',
            'range_to' => 'decimal:2',
            'amount' => 'decimal:2',
            'excess_of' => 'decimal:2',
            'percentage' => 'decimal:6',
            'is_active' => 'boolean',
        ];
    }

    public function occupancySubGroup(): BelongsTo
    {
        return $this->belongsTo(OccupancySubGroup::class);
    }
}
