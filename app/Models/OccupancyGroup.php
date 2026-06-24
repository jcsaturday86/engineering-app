<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OccupancyGroup extends Model
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
     * Sub-groups under this occupancy group.
     */
    public function subGroups(): HasMany
    {
        return $this->hasMany(OccupancySubGroup::class)->orderBy('sort_order');
    }

    /**
     * Divisions under this occupancy group.
     */
    public function divisions(): HasMany
    {
        return $this->hasMany(OccupancyDivision::class);
    }
}
