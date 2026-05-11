<?php
// tests/Feature/MemberApiTest.php
uses(\Tests\CoopTestCase::class);

// ── GET /api/v1/members ───────────────────────────────────────────────────

it('returns paginated members for authenticated loan officer', function () {
    $this->makeMember(); $this->makeMember(); $this->makeMember();
    $officer = $this->loanOfficer();

    $response = $this->actingAs($officer)->getJson('/api/v1/members');

    $response->assertOk()
        ->assertJsonStructure(['data', 'links', 'meta']);
})->group('members');

it('requires authentication to list members', function () {
    $this->getJson('/api/v1/members')->assertUnauthorized();
})->group('members');

it('staff can view members but not create', function () {
    $staff = $this->staff();

    $this->actingAs($staff)->getJson('/api/v1/members')->assertOk();

    $this->actingAs($staff)->postJson('/api/v1/members', [
        'first_name'    => 'Test',
        'last_name'     => 'User',
        'member_no'     => 'M-TEST-001',
        'status'        => 'REGULAR',
        'member_status' => 'ACTIVE',
    ])->assertForbidden();
})->group('members');

// ── POST /api/v1/members ──────────────────────────────────────────────────

it('loan officer can create a member', function () {
    $officer = $this->loanOfficer();

    $response = $this->actingAs($officer)->postJson('/api/v1/members', [
        'first_name'     => 'Maria',
        'last_name'      => 'Santos',
        'member_no'      => 'M-2026-0001',
        'status'         => 'REGULAR',
        'member_status'  => 'ACTIVE',
        'monthly_salary' => 20000,
        'share_capital'  => 2000,
        'date_hired'     => '2023-01-01',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.first_name', 'Maria')
        ->assertJsonPath('data.last_name', 'Santos');
})->group('members');

it('rejects duplicate member_no', function () {
    $this->makeMember(['member_no' => 'M-DUP-001']);
    $officer = $this->loanOfficer();

    $this->actingAs($officer)->postJson('/api/v1/members', [
        'first_name'    => 'Another',
        'last_name'     => 'Person',
        'member_no'     => 'M-DUP-001',
        'status'        => 'REGULAR',
        'member_status' => 'ACTIVE',
    ])->assertUnprocessable()
      ->assertJsonValidationErrors(['member_no']);
})->group('members');

// ── DELETE /api/v1/members/{id} ───────────────────────────────────────────

it('loan officer cannot delete a member', function () {
    $member  = $this->makeMember();
    $officer = $this->loanOfficer();

    $this->actingAs($officer)
        ->deleteJson("/api/v1/members/{$member->id}")
        ->assertForbidden();
})->group('members');

it('super admin can soft-delete a member', function () {
    $member = $this->makeMember();
    $admin  = $this->superAdmin();

    $this->actingAs($admin)
        ->deleteJson("/api/v1/members/{$member->id}")
        ->assertOk();

    $this->assertSoftDeleted('members', ['id' => $member->id]);
})->group('members');
