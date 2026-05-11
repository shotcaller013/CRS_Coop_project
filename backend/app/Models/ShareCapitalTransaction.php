<?php
// app/Models/ShareCapitalTransaction.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShareCapitalTransaction extends Model
{
    use SoftDeletes;

    protected $table = 'share_capital_transactions';

    protected $fillable = [
        'member_id', 'type', 'direction', 'amount', 'balance_after',
        'or_number', 'transaction_date', 'remarks', 'reference_no',
        'posted_by',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'balance_after'    => 'decimal:2',
        'transaction_date' => 'date',
    ];

    // ── Relations ────────────────────────────────────────────

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    // ── Computed attributes ──────────────────────────────────

    /** Signed amount: positive for credits, negative for debits */
    public function getSignedAmountAttribute(): float
    {
        return $this->direction === 'credit'
            ? (float) $this->amount
            : -(float) $this->amount;
    }

    /** True when this transaction reduces the balance */
    public function getIsDebitAttribute(): bool
    {
        return $this->direction === 'debit';
    }

    /** Human label for type */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'opening'    => 'Opening balance',
            'deposit'    => 'Deposit',
            'withdrawal' => 'Withdrawal',
            'dividend'   => 'Dividend',
            'adjustment' => 'Adjustment',
            default      => ucfirst($this->type),
        };
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopeCredits($query)
    {
        return $query->where('direction', 'credit');
    }

    public function scopeDebits($query)
    {
        return $query->where('direction', 'debit');
    }

    public function scopeForPeriod($query, string $from, string $to)
    {
        return $query->whereBetween('transaction_date', [$from, $to]);
    }
}
