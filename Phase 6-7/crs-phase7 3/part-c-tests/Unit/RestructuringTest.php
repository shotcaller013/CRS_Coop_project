<?php
// tests/Unit/RestructuringTest.php
use App\Services\RestructuringService;

uses(\Tests\CoopTestCase::class);

beforeEach(function () {
    $this->service = app(RestructuringService::class);
});

it('generates a correctly sized schedule for the new terms', function () {
    $schedule = $this->service->computeSchedule(
        amount:      45000,
        termMonths:  18,
        annualRate:  12.00,
        frequency:   'MONTHLY',
        firstDueDate:'2026-08-01'
    );

    expect($schedule)->toHaveCount(18);
})->group('restructuring');

it('balance reaches zero after restructured schedule', function () {
    $schedule = $this->service->computeSchedule(
        amount:      20000,
        termMonths:  12,
        annualRate:  14.00,
        frequency:   'BI-MONTHLY',
        firstDueDate:'2026-07-15'
    );

    $lastBalance = (float) end($schedule)['balance'];
    expect($lastBalance)->toBe(0.00);
})->group('restructuring');

it('throws when trying to restructure a non-active loan', function () {
    $loanType = $this->makeLoanType();
    $member   = $this->makeMember();
    $loan     = $this->makeActiveLoan($member, $loanType);
    $loan->update(['status' => 'CLOSED']);

    expect(fn() => $this->service->assertCanRestructure($loan))
        ->toThrow(\InvalidArgumentException::class);
})->group('restructuring');

it('generates RS number in correct format', function () {
    $loanType = $this->makeLoanType();
    $member   = $this->makeMember();
    $loan     = $this->makeActiveLoan($member, $loanType);

    $officer = $this->manager();
    $this->actingAs($officer);

    $record = $this->service->execute($loan, [
        'new_amount'         => $loan->remaining_balance,
        'new_term_months'    => 24,
        'new_annual_rate'    => 12.00,
        'new_frequency'      => 'MONTHLY',
        'new_first_due_date' => now()->addMonth()->toDateString(),
        'effective_date'     => now()->toDateString(),
        'reason'             => 'Member requested extended term due to financial hardship.',
    ]);

    expect($record->restructuring_no)->toMatch('/^RS-\d{4}-\d{5}$/');
})->group('restructuring');
