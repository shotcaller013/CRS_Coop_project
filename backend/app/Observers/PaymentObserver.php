<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\AuditLogService;
use App\Services\NotificationService;

class PaymentObserver
{
    public function __construct(
        private readonly AuditLogService $audit,
        private readonly NotificationService $notif,
    ) {}

    public function created(Payment $payment): void
    {
        $this->audit->recordCreated($payment);
        $this->notif->paymentReceived($payment);
    }

    public function updated(Payment $payment): void
    {
        $this->audit->recordUpdated($payment);
    }

    public function deleted(Payment $payment): void
    {
        $this->audit->recordDeleted($payment);
    }
}
