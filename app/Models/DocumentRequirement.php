<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A document the Engineering Office requires for a given permit type.
 *
 * Two levels deep: a row with no parent is either a standalone requirement or a
 * heading (is_uploadable = false) that groups child rows. Which documents a
 * client must attach is entirely data-driven from this table — no permit type
 * is special-cased in code, so a type with zero rows simply requires nothing.
 */
class DocumentRequirement extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'permit_type_id',
        'parent_id',
        'name',
        'condition_note',
        'requirement_level',
        'is_uploadable',
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
            'is_uploadable' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function permitType(): BelongsTo
    {
        return $this->belongsTo(PermitType::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function applicationRequirements(): HasMany
    {
        return $this->hasMany(ApplicationRequirement::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Only rows a client can actually attach a file to — excludes heading rows.
     */
    public function scopeUploadable(Builder $query): Builder
    {
        return $query->where('is_uploadable', true);
    }

    public function scopeMandatory(Builder $query): Builder
    {
        return $query->where('requirement_level', 'mandatory');
    }

    /**
     * Tailwind badge classes for this requirement's obligation level.
     */
    public function levelColor(): string
    {
        return match ($this->requirement_level) {
            'mandatory' => 'bg-red-100 text-red-700',
            'conditional' => 'bg-amber-100 text-amber-700',
            default => 'bg-gray-100 text-gray-600',
        };
    }

    public function levelLabel(): string
    {
        return ucfirst($this->requirement_level);
    }
}
