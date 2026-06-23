<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingItem extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'billing_id',
        'category',
        'description',
        'amount',
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
            'amount' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Billing this item belongs to.
     */
    public function billing(): BelongsTo
    {
        return $this->belongsTo(Billing::class);
    }
}
