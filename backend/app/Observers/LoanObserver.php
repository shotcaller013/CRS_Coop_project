<?php

namespace App\Observers;

use App\Models\Loan;
use App\Services\AuditLogService;
use App\Services\NotificationService;

class LoanObserver
{
    public function __construct(
        private readonly AuditLogService $audit,
        private readonly NotificationService $notif,
    ) {}

    public function created(Loan $loan): void
    {
        $this->audit->recordCreated($loan);
        $this->notif->approvalNeeded($loan);
    }

    public function updated(Loan $loan): void
    {
        $this->audit->recordUpdated($loan);

        if ($loan->wasChanged('status')) {
            match ($loan->status) {
                'APPROVED' => $this->notif->loanApproved($loan),
                'CLOSED'   => $this->notif->loanClosed($loan),
                default    => null,
            };
        }
    }

    public function deleted(Loan $loan): void
    {
        $this->audit->recordDeleted($loan);
    }

    public function restored(Loan $loan): void
    {
        $this->audit->recordRestored($loan);
    }
}
