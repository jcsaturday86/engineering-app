<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeType extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'fee_category_id',
        'code',
        'name',
        'description',
        'computation_method',
        'has_excess',
        'has_minimum',
        'has_maximum',
        'is_active',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'has_excess' => 'boolean',
            'has_minimum' => 'boolean',
            'has_maximum' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Fee category this fee type belongs to.
     */
    public function feeCategory(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class);
    }

    /**
     * Fee schedules for this fee type.
     */
    public function feeSchedules(): HasMany
    {
        return $this->hasMany(FeeSchedule::class);
    }
}
