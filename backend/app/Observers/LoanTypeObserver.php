<?php
// app/Observers/LoanTypeObserver.php
namespace App\Observers;

use App\Models\LoanType;
use App\Services\AuditLogService;

class LoanTypeObserver
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function created(LoanType $loanType): void
    {
        $this->audit->recordCreated($loanType);
    }

    public function updated(LoanType $loanType): void
    {
        $this->audit->recordUpdated($loanType);
    }

    public function deleted(LoanType $loanType): void
    {
        $this->audit->recordDeleted($loanType);
    }
}
