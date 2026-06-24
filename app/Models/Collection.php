<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Collection extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'application_id',
        'applicationable_type',
        'applicationable_id',
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    public function applicationable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getApplicationAttribute()
    {
        return $this->applicationable;
    }

    public function billing(): BelongsTo
    {
        return $this->belongsTo(Billing::class);
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function collectionDetails(): HasMany
    {
        return $this->hasMany(CollectionDetail::class);
    }

    public function voidTransaction(): HasOne
    {
        return $this->hasOne(VoidTransaction::class);
    }
}
