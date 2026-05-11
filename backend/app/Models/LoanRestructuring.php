<?php
// app/Models/LoanRestructuring.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanRestructuring extends Model
{
    // Immutable audit record — no updated_at, no soft-delete
    public $timestamps   = false;
    const CREATED_AT     = 'created_at';

    protected $table     = 'loan_restructurings';

    protected $fillable  = [
        'loan_id', 'restructuring_no', 'effective_date',
        'old_remaining_balance', 'old_term_months', 'old_annual_rate',
        'old_frequency', 'old_periods_remaining',
        'new_amount', 'new_term_months', 'new_annual_rate',
        'new_frequency', 'new_first_due_date',
        'new_n_periods', 'new_total_payment', 'new_total_interest',
        'periods_voided', 'reason',
        'approved_by', 'approved_by_name',
        'created_at',
    ];

    protected $casts = [
        'effective_date'          => 'date',
        'new_first_due_date'      => 'date',
        'old_remaining_balance'   => 'decimal:2',
        'old_annual_rate'         => 'decimal:4',
        'new_amount'              => 'decimal:2',
        'new_annual_rate'         => 'decimal:4',
        'new_total_payment'       => 'decimal:2',
        'new_total_interest'      => 'decimal:2',
        'created_at'              => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── Helpers ──────────────────────────────────────────────

    /** How many times this loan has been restructured (including this record) */
    public function getRestructuringCountAttribute(): int
    {
        return static::where('loan_id', $this->loan_id)->count();
    }

    /** Frequency label */
    public function getFrequencyLabelAttribute(): string
    {
        return match ($this->new_frequency) {
            'MONTHLY'    => 'Monthly',
            'BI-MONTHLY' => 'Bi-Monthly',
            'WEEKLY'     => 'Weekly',
            default      => ucfirst(strtolower($this->new_frequency)),
        };
    }
}
