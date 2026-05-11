<?php

// app/Providers/AuthServiceProvider.php
// Add these entries to the $policies array

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\Bill;
use App\Models\Loan;
use App\Models\LoanRestructuring;
use App\Models\Member;
use App\Models\NotificationLog;
use App\Models\ShareCapitalTransaction;
use App\Models\User;
use App\Policies\AuditLogPolicy;
use App\Policies\BeneficiaryPolicy;
use App\Policies\BillPolicy;
use App\Policies\LoanPolicy;
use App\Policies\MemberPolicy;
use App\Policies\NotificationLogPolicy;
use App\Policies\RestructuringPolicy;
use App\Policies\ShareCapitalPolicy;
use App\Policies\UserManagementPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Member::class                  => MemberPolicy::class,
        Loan::class                    => LoanPolicy::class,
        AuditLog::class                => AuditLogPolicy::class,
        Beneficiary::class             => BeneficiaryPolicy::class,
        ShareCapitalTransaction::class  => ShareCapitalPolicy::class,
        LoanRestructuring::class        => RestructuringPolicy::class,
        NotificationLog::class          => NotificationLogPolicy::class,
        User::class                    => UserManagementPolicy::class,
        Bill::class                    => BillPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
