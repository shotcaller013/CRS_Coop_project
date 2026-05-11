<?php

namespace App\Providers;

use App\Models\CoopProfile;
use App\Models\Loan;
use App\Models\LoanType;
use App\Models\Member;
use App\Models\Payment;
use App\Observers\CoopProfileObserver;
use App\Observers\LoanObserver;
use App\Observers\LoanTypeObserver;
use App\Observers\MemberObserver;
use App\Observers\PaymentObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Member::observe(MemberObserver::class);
        Loan::observe(LoanObserver::class);
        Payment::observe(PaymentObserver::class);
        LoanType::observe(LoanTypeObserver::class);
        CoopProfile::observe(CoopProfileObserver::class);
    }
}
