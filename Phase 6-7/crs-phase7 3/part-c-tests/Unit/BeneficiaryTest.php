<?php
// tests/Unit/BeneficiaryTest.php
use App\Models\Beneficiary;
use App\Services\BeneficiaryService;

uses(\Tests\CoopTestCase::class);

beforeEach(function () {
    $this->member  = $this->makeMember();
    $this->service = app(BeneficiaryService::class);
});

it('reports incomplete when no primary beneficiary is declared', function () {
    $status = $this->service->getCompletionStatus($this->member);

    expect($status['is_complete'])->toBeFalse();
    expect($status['primary_count'])->toBe(0);
    expect($status['issues'])->not->toBeEmpty();
})->group('beneficiaries');

it('reports incomplete when primary shares do not total 100 percent', function () {
    Beneficiary::create([
        'member_id'        => $this->member->id,
        'type'             => 'primary',
        'first_name'       => 'Ana',
        'last_name'        => 'Cruz',
        'relationship'     => 'Spouse',
        'share_percentage' => 60.00,
    ]);

    $status = $this->service->getCompletionStatus($this->member);

    expect($status['is_complete'])->toBeFalse();
    expect($status['total_share'])->toBe(60.00);
})->group('beneficiaries');

it('reports complete when single primary beneficiary has 100 percent', function () {
    Beneficiary::create([
        'member_id'        => $this->member->id,
        'type'             => 'primary',
        'first_name'       => 'Ana',
        'last_name'        => 'Cruz',
        'relationship'     => 'Spouse',
        'share_percentage' => 100.00,
    ]);

    $status = $this->service->getCompletionStatus($this->member);

    expect($status['is_complete'])->toBeTrue();
    expect($status['total_share'])->toBe(100.00);
})->group('beneficiaries');

it('reports incomplete when a minor beneficiary has no guardian', function () {
    Beneficiary::create([
        'member_id'        => $this->member->id,
        'type'             => 'primary',
        'first_name'       => 'Nico',
        'last_name'        => 'Cruz',
        'relationship'     => 'Child',
        'birthdate'        => now()->subYears(10)->toDateString(), // minor
        'share_percentage' => 100.00,
        'guardian_name'    => null, // missing
    ]);

    $status = $this->service->getCompletionStatus($this->member);

    expect($status['is_complete'])->toBeFalse();
    expect(implode(' ', $status['issues']))->toContain('guardian');
})->group('beneficiaries');
