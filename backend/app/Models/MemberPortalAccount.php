<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberPortalAccount extends Model
{
    protected $fillable = [
        'member_id', 'username', 'email', 'password_hash',
        'force_password_change', 'modules_json', 'is_active',
        'last_login_at', 'created_by',
    ];

    protected $hidden = ['password_hash'];

    protected $casts = [
        'modules_json'          => 'array',
        'force_password_change' => 'boolean',
        'is_active'             => 'boolean',
        'last_login_at'         => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(MemberPortalSession::class, 'account_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(MemberPortalAuditLog::class, 'account_id');
    }

    public function getModulesAttribute(): array
    {
        $default = ['dashboard', 'loans', 'payments', 'shareCapital', 'beneficiaries', 'profile'];
        $v = $this->modules_json;
        if (is_array($v)) return $v;
        if (is_string($v)) {
            $decoded = json_decode($v, true);
            return is_array($decoded) ? $decoded : $default;
        }
        return $default;
    }

    public function getMemberNameAttribute(): string
    {
        if (! $this->relationLoaded('member')) return '';
        $m = $this->member;
        return trim(implode(' ', array_filter([$m->first_name, $m->middle_name, $m->last_name])));
    }
}
