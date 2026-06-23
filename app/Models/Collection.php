<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Collection extends Model
{
    use LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'application_id',
        'billing_id',
        'or_number',
        'or_date',
        'paid_by',
        'amount_due',
        'amount_received',
        'change_amount',
        'payment_mode',
        'bank_name',
        'check_number',
        'check_date',
        'online_reference',
        'collected_by',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'or_date' => 'date',
            'amount_due' => 'decimal:2',
            'amount_received' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'check_date' => 'date',
        ];
    }

    /**
     * Get activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    /**
     * Application this collection belongs to.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Billing this collection is for.
     */
    public function billing(): BelongsTo
    {
        return $this->belongsTo(Billing::class);
    }

    /**
     * User who collected the payment.
     */
    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    /**
     * Payment detail breakdown.
     */
    public function collectionDetails(): HasMany
    {
        return $this->hasMany(CollectionDetail::class);
    }

    /**
     * Void transaction for this collection, if any.
     */
    public function voidTransaction(): HasOne
    {
        return $this->hasOne(VoidTransaction::class);
    }
}
