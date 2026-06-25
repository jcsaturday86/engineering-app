<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificationZoningFee extends Model
{
    protected $table = 'certification_zoning_fees';

    protected $fillable = [
        'occupancy_sub_group_id',
        'amount',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function occupancySubGroup(): BelongsTo
    {
        return $this->belongsTo(OccupancySubGroup::class);
    }
}
