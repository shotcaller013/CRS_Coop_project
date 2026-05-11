<?php
// tests/Unit/AmortizationTest.php
use App\Services\LoanService;

// ── Test suite: Amortization schedule computation ─────────────────────────
// The amortization math is verified against Yang2x's actual CRS paper sample.
// If ANY of these tests fail, the loan packet numbers will be wrong.
// These tests must pass before every deployment.

uses(\Tests\CoopTestCase::class);

beforeEach(function () {
    $this->service = app(LoanService::class);
});

it('matches the CRS paper sample exactly — ₱60k bi-monthly 36 months', function () {
    $schedule = $this->service->computeSchedule(
        amount:      60000,
        termMonths:  36,
        annualRate:  12.00,
        frequency:   'BI-MONTHLY',
        firstDueDate:'2026-02-15'
    );

    // Yang2x verified these against the physical form
    expect($schedule)->toHaveCount(72);
    expect((float) $schedule[0]['amount_due'])->toBe(1433.33);
    expect((float) $schedule[71]['amount_due'])->toBe(850.00);

    $totalPayment = array_sum(array_column($schedule, 'amount_due'));
    expect(round($totalPayment, 2))->toBe(73800.00);
})->group('amortization', 'critical');

it('generates correct period count for monthly frequency', function () {
    $schedule = $this->service->computeSchedule(
        amount:      30000,
        termMonths:  12,
        annualRate:  12.00,
        frequency:   'MONTHLY',
        firstDueDate:'2026-01-31'
    );

    expect($schedule)->toHaveCount(12);
    expect((float) $schedule[0]['principal'])->toBe(2500.00);
    expect((float) $schedule[0]['interest'])->toBe(300.00);
    expect((float) $schedule[0]['amount_due'])->toBe(2800.00);
    expect((float) $schedule[0]['balance'])->toBe(27500.00);
})->group('amortization');

it('generates correct period count for bi-monthly frequency', function () {
    $schedule = $this->service->computeSchedule(
        amount:      24000,
        termMonths:  12,
        annualRate:  12.00,
        frequency:   'BI-MONTHLY',
        firstDueDate:'2026-01-15'
    );

    expect($schedule)->toHaveCount(24); // 12 months × 2
})->group('amortization');

it('generates correct period count for weekly frequency', function () {
    $schedule = $this->service->computeSchedule(
        amount:      10000,
        termMonths:  3,
        annualRate:  12.00,
        frequency:   'WEEKLY',
        firstDueDate:'2026-01-07'
    );

    expect($schedule)->toHaveCount(12); // 3 months × 4
})->group('amortization');

it('balance reaches zero on last period', function () {
    $schedule = $this->service->computeSchedule(
        amount:      50000,
        termMonths:  24,
        annualRate:  15.00,
        frequency:   'MONTHLY',
        firstDueDate:'2026-02-01'
    );

    $lastBalance = (float) end($schedule)['balance'];
    expect($lastBalance)->toBe(0.00);
})->group('amortization');

it('interest is always computed on diminishing balance', function () {
    $schedule = $this->service->computeSchedule(
        amount:      12000,
        termMonths:  6,
        annualRate:  12.00,
        frequency:   'MONTHLY',
        firstDueDate:'2026-02-01'
    );

    // Each period's interest should be less than the previous
    for ($i = 1; $i < count($schedule); $i++) {
        expect((float) $schedule[$i]['interest'])
            ->toBeLessThan((float) $schedule[$i - 1]['interest']);
    }
})->group('amortization');

it('due dates advance correctly for monthly frequency', function () {
    $schedule = $this->service->computeSchedule(
        amount:      6000,
        termMonths:  3,
        annualRate:  12.00,
        frequency:   'MONTHLY',
        firstDueDate:'2026-01-31'
    );

    expect($schedule[0]['due_date'])->toBe('2026-01-31');
    expect($schedule[1]['due_date'])->toBe('2026-02-28'); // or 2026-02-28 depending on Carbon
    expect($schedule[2]['due_date'])->toBe('2026-03-31');
})->group('amortization');
