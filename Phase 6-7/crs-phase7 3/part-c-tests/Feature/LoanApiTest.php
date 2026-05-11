<?php
// tests/Feature/LoanApiTest.php
uses(\Tests\CoopTestCase::class);

// ── POST /api/v1/loans ────────────────────────────────────────────────────

it('loan officer can submit a loan application', function () {
    $officer  = $this->loanOfficer();
    $member   = $this->makeMember();
    $loanType = $this->makeLoanType();

    $response = $this->actingAs($officer)->postJson('/api/v1/loans', [
        'member_id'        => $member->id,
        'loan_type_id'     => $loanType->id,
        'amount'           => 30000,
        'term_months'      => 12,
        'frequency'        => 'MONTHLY',
        'annual_rate'      => 12.00,
        'application_date' => now()->toDateString(),
        'first_due_date'   => now()->addMonth()->toDateString(),
        'purpose'          => 'Emergency medical expense',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'PENDING')
        ->assertJsonStructure(['data' => ['id', 'loan_no', 'amount', 'status']]);
})->group('loans', 'critical');

it('auto-generates a unique loan number in LN-YYYY-NNNNN format', function () {
    $officer  = $this->loanOfficer();
    $member   = $this->makeMember();
    $loanType = $this->makeLoanType();

    $r1 = $this->actingAs($officer)->postJson('/api/v1/loans', [
        'member_id' => $member->id, 'loan_type_id' => $loanType->id,
        'amount' => 10000, 'term_months' => 6, 'frequency' => 'MONTHLY',
        'annual_rate' => 12.00, 'application_date' => now()->toDateString(),
        'first_due_date' => now()->addMonth()->toDateString(), 'purpose' => 'Test',
    ]);
    $r2 = $this->actingAs($officer)->postJson('/api/v1/loans', [
        'member_id' => $member->id, 'loan_type_id' => $loanType->id,
        'amount' => 10000, 'term_months' => 6, 'frequency' => 'MONTHLY',
        'annual_rate' => 12.00, 'application_date' => now()->toDateString(),
        'first_due_date' => now()->addMonth()->toDateString(), 'purpose' => 'Test 2',
    ]);

    $n1 = $r1->json('data.loan_no');
    $n2 = $r2->json('data.loan_no');

    expect($n1)->toMatch('/^LN-\d{4}-\d{5}$/');
    expect($n2)->toMatch('/^LN-\d{4}-\d{5}$/');
    expect($n1)->not->toBe($n2);
})->group('loans');

it('creates the full amortization schedule on loan submission', function () {
    $officer  = $this->loanOfficer();
    $member   = $this->makeMember();
    $loanType = $this->makeLoanType();

    $response = $this->actingAs($officer)->postJson('/api/v1/loans', [
        'member_id' => $member->id, 'loan_type_id' => $loanType->id,
        'amount' => 12000, 'term_months' => 12, 'frequency' => 'MONTHLY',
        'annual_rate' => 12.00, 'application_date' => now()->toDateString(),
        'first_due_date' => now()->addMonth()->toDateString(), 'purpose' => 'Test',
    ]);

    $loanId = $response->json('data.id');
    $this->assertDatabaseCount('amortization_schedules', 12);

    // First period: principal=1000, interest=120, amount=1120
    $this->assertDatabaseHas('amortization_schedules', [
        'loan_id'   => $loanId,
        'period_no' => 1,
        'principal' => '1000.00',
        'interest'  => '120.00',
        'amount_due'=> '1120.00',
    ]);
})->group('loans', 'critical');

// ── POST /api/v1/loans/{id}/approve ───────────────────────────────────────

it('manager can approve a pending loan', function () {
    $manager  = $this->manager();
    $loanType = $this->makeLoanType();
    $member   = $this->makeMember();
    $loan     = \App\Models\Loan::factory()->create([
        'member_id'    => $member->id,
        'loan_type_id' => $loanType->id,
        'status'       => 'PENDING',
        'amount'       => 20000,
    ]);

    $this->actingAs($manager)
        ->postJson("/api/v1/loans/{$loan->id}/approve")
        ->assertOk();

    $this->assertDatabaseHas('loans', ['id' => $loan->id, 'status' => 'APPROVED']);
})->group('loans');

it('loan officer cannot approve loans', function () {
    $officer  = $this->loanOfficer();
    $loanType = $this->makeLoanType();
    $member   = $this->makeMember();
    $loan     = \App\Models\Loan::factory()->create([
        'member_id'    => $member->id,
        'loan_type_id' => $loanType->id,
        'status'       => 'PENDING',
        'amount'       => 20000,
    ]);

    $this->actingAs($officer)
        ->postJson("/api/v1/loans/{$loan->id}/approve")
        ->assertForbidden();
})->group('loans');

// ── GET /api/v1/loans/pipeline ────────────────────────────────────────────

it('pipeline returns loans grouped by status', function () {
    $loanType = $this->makeLoanType();
    $member   = $this->makeMember();
    \App\Models\Loan::factory()->create(['member_id'=>$member->id,'loan_type_id'=>$loanType->id,'status'=>'PENDING','amount'=>10000]);
    \App\Models\Loan::factory()->create(['member_id'=>$member->id,'loan_type_id'=>$loanType->id,'status'=>'ACTIVE', 'amount'=>20000]);

    $officer  = $this->loanOfficer();
    $response = $this->actingAs($officer)->getJson('/api/v1/loans/pipeline');

    $response->assertOk()
        ->assertJsonStructure(['data' => ['PENDING', 'ACTIVE']]);
})->group('loans');
