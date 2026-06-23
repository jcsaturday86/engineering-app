<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentItem extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'assessment_id',
        'fee_category_id',
        'fee_type_id',
        'fee_code',
        'description',
        'quantity',
        'unit_fee',
        'excess_fee',
        'inspection_fee',
        'amount',
        'computation_details',
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
            'quantity' => 'decimal:4',
            'unit_fee' => 'decimal:4',
            'excess_fee' => 'decimal:4',
            'inspection_fee' => 'decimal:4',
            'amount' => 'decimal:2',
            'computation_details' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Assessment this item belongs to.
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    /**
     * Fee category.
     */
    public function feeCategory(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class);
    }

    /**
     * Fee type.
     */
    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class);
    }
}
