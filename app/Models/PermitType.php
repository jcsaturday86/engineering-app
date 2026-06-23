<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermitType extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
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
     * Fee categories for this permit type.
     */
    public function feeCategories(): HasMany
    {
        return $this->hasMany(FeeCategory::class);
    }

    /**
     * Applications for this permit type.
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
