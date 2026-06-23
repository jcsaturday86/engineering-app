<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeCategory extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'permit_type_id',
        'code',
        'name',
        'description',
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
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Permit type this fee category belongs to.
     */
    public function permitType(): BelongsTo
    {
        return $this->belongsTo(PermitType::class);
    }

    /**
     * Fee types under this category.
     */
    public function feeTypes(): HasMany
    {
        return $this->hasMany(FeeType::class);
    }
}
