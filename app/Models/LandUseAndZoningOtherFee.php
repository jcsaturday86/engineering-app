<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandUseAndZoningOtherFee extends Model
{
    protected $table = 'land_use_and_zoning_other_fees';

    protected $fillable = [
        'name',
        'code',
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
}
