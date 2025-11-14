<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformConnection extends Model
{
    protected $fillable = [
        'workspace_id',
        'provider',
        'external_account_id',
        'name',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'extra_data',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'extra_data' => 'array',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
