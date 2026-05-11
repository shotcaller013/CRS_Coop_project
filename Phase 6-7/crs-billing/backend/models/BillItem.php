<?php
// app/Models/BillItem.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillItem extends Model
{
    protected $fillable = [
        'bill_id', 'schedule_id', 'member_id', 'loan_id',
        'amount_due', 'amount_paid', 'status',
    ];

    protected $casts = [
        'amount_due'  => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(AmortizationSchedule::class, 'schedule_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }
}


// ─────────────────────────────────────────────────────────────────────────
// app/Models/BillRemittance.php
// Tracks each uploaded remittance — a bill can have multiple partial ones
// ─────────────────────────────────────────────────────────────────────────
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillRemittance extends Model
{
    protected $table   = 'bill_remittances';
    public $timestamps = false;
    const CREATED_AT   = 'created_at';

    protected $fillable = [
        'bill_id', 'or_number', 'amount',
        'remittance_date', 'file_path',
        'notes', 'posted_by', 'created_at',
    ];

    protected $casts = [
        'amount'          => 'decimal:2',
        'remittance_date' => 'date',
        'created_at'      => 'datetime',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}
