<?php
// tests/Unit/EligibilityTest.php
use App\Services\EligibilityService;

uses(\Tests\CoopTestCase::class);

beforeEach(function () {
    $this->loanType = $this->makeLoanType([
        'allowed_emp_statuses' => json_encode(['REGULAR']),
        'min_tenure_months'    => 6,
        'min_share_capital'    => 1000,
        'allow_concurrent'     => false,
        'max_amount'           => 50000,
        'amount_cap_method'    => 'fixed',
    ]);
    $this->service = app(EligibilityService::class);
});

it('passes all checks for a fully eligible member', function () {
    $member = $this->makeMember(['status' => 'REGULAR', 'share_capital' => 5000]);
    $result = $this->service->check($member, $this->loanType, 30000);

    expect($result['eligible'])->toBeTrue();
    expect($result['failed'])->toBeEmpty();
})->group('eligibility');

it('fails status check for probationary member on REGULAR-only loan', function () {
    $member = $this->makeMember(['status' => 'PROBI']);
    $result = $this->service->check($member, $this->loanType, 30000);

    expect($result['eligible'])->toBeFalse();
    expect($result['failed'])->toContain('status');
})->group('eligibility');

it('fails tenure check for member hired 3 months ago', function () {
    $member = $this->makeMember(['date_hired' => now()->subMonths(3)->toDateString()]);
    $result = $this->service->check($member, $this->loanType, 30000);

    expect($result['eligible'])->toBeFalse();
    expect($result['failed'])->toContain('tenure');
})->group('eligibility');

it('fails share capital check when balance is below minimum', function () {
    $member = $this->makeMember(['share_capital' => 500]);
    $result = $this->service->check($member, $this->loanType, 30000);

    expect($result['eligible'])->toBeFalse();
    expect($result['failed'])->toContain('share_capital');
})->group('eligibility');

it('fails amount cap check when requested amount exceeds fixed ceiling', function () {
    $member = $this->makeMember();
    $result = $this->service->check($member, $this->loanType, 75000);

    expect($result['eligible'])->toBeFalse();
    expect($result['failed'])->toContain('amount_cap');
})->group('eligibility');
