<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberPortalAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'account_id', 'member_id', 'action', 'detail', 'ip_address', 'user_agent',
    ];

    protected $casts = ['created_at' => 'datetime'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(MemberPortalAccount::class, 'account_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
