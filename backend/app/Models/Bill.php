<?php
// app/Models/Bill.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bill extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'bill_no', 'company_id', 'status',
        'billing_period_start', 'billing_period_end',
        'total_amount', 'amount_remitted',
        'issued_at', 'settled_at',
        'prepared_by', 'notes',
    ];

    protected $casts = [
        'billing_period_start' => 'date',
        'billing_period_end'   => 'date',
        'total_amount'         => 'decimal:2',
        'amount_remitted'      => 'decimal:2',
        'issued_at'            => 'datetime',
        'settled_at'           => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillItem::class)->orderBy('id');
    }

    public function remittances(): HasMany
    {
        return $this->hasMany(BillRemittance::class)->orderByDesc('created_at');
    }

    // ── Computed ─────────────────────────────────────────────

    public function getBalanceAttribute(): float
    {
        return round((float)$this->total_amount - (float)$this->amount_remitted, 2);
    }

    public function getIsFullyPaidAttribute(): bool
    {
        return $this->balance <= 0;
    }

    public function getItemCountAttribute(): int
    {
        return $this->items()->count();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'DRAFT'     => 'Draft',
            'ISSUED'    => 'Issued',
            'PARTIAL'   => 'Partial payment',
            'SETTLED'   => 'Settled',
            'CANCELLED' => 'Cancelled',
            default     => ucfirst(strtolower($this->status)),
        };
    }
}
