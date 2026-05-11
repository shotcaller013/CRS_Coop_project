<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Loan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'loan_no',
        'member_id',
        'loan_type_id',
        'amount',
        'term_months',
        'frequency',
        'annual_rate',
        'purpose',
        'co_maker_1_id',
        'co_maker_2_id',
        'status',
        'total_payment',
        'total_interest',
        'n_periods',
        'first_payment_amt',
        'last_payment_amt',
        'application_date',
        'approval_date',
        'first_due_date',
        'end_date',
        'approved_by_hr',
        'approved_by_coop',
        'signed_form_url',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount'            => 'decimal:2',
        'annual_rate'       => 'decimal:4',
        'total_payment'     => 'decimal:2',
        'total_interest'    => 'decimal:2',
        'first_payment_amt' => 'decimal:2',
        'last_payment_amt'  => 'decimal:2',
        'application_date'  => 'date',
        'approval_date'     => 'date',
        'first_due_date'    => 'date',
        'end_date'          => 'date',
    ];

    // ── Relationships ─────────────────────────────────────────

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function loanType(): BelongsTo
    {
        return $this->belongsTo(LoanType::class, 'loan_type_id');
    }

    public function coMaker1(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'co_maker_1_id');
    }

    public function coMaker2(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'co_maker_2_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function amortizationSchedules(): HasMany
    {
        return $this->hasMany(AmortizationSchedule::class, 'loan_id')->orderBy('period_no');
    }

    public function restructurings(): HasMany
    {
        return $this->hasMany(LoanRestructuring::class)->orderByDesc('created_at');
    }

    public function latestRestructuring(): HasOne
    {
        return $this->hasOne(LoanRestructuring::class)->latestOfMany('created_at');
    }

    // ── Accessors ─────────────────────────────────────────────

    public function getIsRestructuredAttribute(): bool
    {
        return $this->restructurings()->exists();
    }

    public function getRestructuringCountAttribute(): int
    {
        return $this->restructurings()->count();
    }

    public function getRemainingBalanceAttribute(): float
    {
        return (float) $this->amortizationSchedules()
            ->whereIn('status', ['PENDING', 'PARTIAL', 'OVERDUE'])
            ->selectRaw('SUM(amount_due - paid_amount) as remaining')
            ->value('remaining') ?? 0.0;
    }

    public function getTotalPenaltyOutstandingAttribute(): float
    {
        return (float) $this->amortizationSchedules()
            ->where('status', 'OVERDUE')
            ->sum('penalty_amount');
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForMember($query, int $memberId)
    {
        return $query->where('member_id', $memberId);
    }
}
