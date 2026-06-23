<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OccupancyDivision extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'occupancy_group_id',
        'code',
        'name',
        'assessment_mode',
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
            'is_active' => 'boolean',
        ];
    }

    /**
     * Occupancy group this division belongs to.
     */
    public function occupancyGroup(): BelongsTo
    {
        return $this->belongsTo(OccupancyGroup::class);
    }
}
