<?php
// tests/Feature/PaymentApiTest.php
uses(\Tests\CoopTestCase::class);

it('loan officer can record a payment against a schedule period', function () {
    $officer  = $this->loanOfficer();
    $loanType = $this->makeLoanType();
    $member   = $this->makeMember();
    $loan     = $this->makeActiveLoan($member, $loanType);
    $schedule = $loan->amortizationSchedules()->where('period_no', 1)->first();

    $response = $this->actingAs($officer)->postJson('/api/v1/payments', [
        'loan_id'      => $loan->id,
        'schedule_id'  => $schedule->id,
        'amount_paid'  => $schedule->amount_due,
        'payment_type' => 'full',
        'or_number'    => 'OR-2026-001',
        'payment_date' => now()->toDateString(),
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('amortization_schedules', [
        'id'     => $schedule->id,
        'status' => 'PAID',
    ]);
})->group('payments');

it('payment balance_after is correctly computed', function () {
    $officer  = $this->loanOfficer();
    $loanType = $this->makeLoanType();
    $member   = $this->makeMember();
    $loan     = $this->makeActiveLoan($member, $loanType, ['amount' => 12000, 'term_months' => 12, 'frequency' => 'MONTHLY']);
    $schedule = $loan->amortizationSchedules()->where('period_no', 1)->first();

    $r = $this->actingAs($officer)->postJson('/api/v1/payments', [
        'loan_id'      => $loan->id,
        'schedule_id'  => $schedule->id,
        'amount_paid'  => $schedule->amount_due,
        'payment_type' => 'full',
        'or_number'    => 'OR-001',
        'payment_date' => now()->toDateString(),
    ]);

    $balanceAfter = $r->json('data.balance_after');
    expect($balanceAfter)->toBeLessThan($loan->amount);
})->group('payments');

it('loan closes automatically when all periods are paid', function () {
    $officer  = $this->loanOfficer();
    $loanType = $this->makeLoanType();
    $member   = $this->makeMember();
    // 3-month loan so we can pay it off quickly in a test
    $loan     = $this->makeActiveLoan($member, $loanType, ['amount' => 3000, 'term_months' => 3, 'frequency' => 'MONTHLY']);

    $periods = $loan->amortizationSchedules()->orderBy('period_no')->get();
    foreach ($periods as $i => $period) {
        $this->actingAs($officer)->postJson('/api/v1/payments', [
            'loan_id'      => $loan->id,
            'schedule_id'  => $period->id,
            'amount_paid'  => $period->amount_due,
            'payment_type' => 'full',
            'or_number'    => "OR-{$i}",
            'payment_date' => now()->toDateString(),
        ]);
    }

    $this->assertDatabaseHas('loans', ['id' => $loan->id, 'status' => 'CLOSED']);
})->group('payments', 'critical');
