<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class AdMetricDaily extends Model
{
    protected $table = 'ad_metrics_daily';

    protected $fillable = [
        'workspace_id',
        'ad_account_id',
        'campaign_id',
        'ad_id',
        'provider',
        'date',
        'impressions',
        'clicks',
        'spend',
        'conversions',
        'revenue',
        'extra_data',
    ];

    protected $casts = [
        'date'       => 'date',
        'spend'      => 'decimal:2',
        'revenue'    => 'decimal:2',
        'extra_data' => 'array',
    ];

    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(AdAccount::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
