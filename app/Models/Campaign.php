<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    protected $fillable = [
        'ad_account_id',
        'provider',
        'external_id',
        'name',
        'status',
        'objective',
        'daily_budget',
        'extra_data',
    ];

    protected $casts = [
        'extra_data' => 'array',
        'daily_budget' => 'decimal:2',
    ];

    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(AdAccount::class);
    }

    public function ads(): HasMany
    {
        return $this->hasMany(Ad::class);
    }
}
