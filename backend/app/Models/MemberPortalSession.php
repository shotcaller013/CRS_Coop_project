<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberPortalSession extends Model
{
    public $timestamps = false;

    protected $fillable = ['account_id', 'token_hash', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(MemberPortalAccount::class, 'account_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
