<?php
// tests/Feature/ReportApiTest.php
uses(\Tests\CoopTestCase::class);

it('loan officer cannot access reports', function () {
    $officer = $this->loanOfficer();
    $this->actingAs($officer)->getJson('/api/v1/reports/collection')->assertForbidden();
    $this->actingAs($officer)->getJson('/api/v1/reports/aging')->assertForbidden();
    $this->actingAs($officer)->getJson('/api/v1/reports/outstanding')->assertForbidden();
})->group('reports');

it('manager can access all three reports', function () {
    $manager = $this->manager();
    $this->actingAs($manager)->getJson('/api/v1/reports/collection')->assertOk();
    $this->actingAs($manager)->getJson('/api/v1/reports/aging')->assertOk();
    $this->actingAs($manager)->getJson('/api/v1/reports/outstanding')->assertOk();
})->group('reports');

it('collection report returns expected and collected amounts', function () {
    $manager  = $this->manager();
    $response = $this->actingAs($manager)->getJson('/api/v1/reports/collection');

    $response->assertOk()
        ->assertJsonStructure(['data' => ['by_loan_type', 'by_loan_status']]);
})->group('reports');

it('aging report returns four buckets', function () {
    $manager  = $this->manager();
    $response = $this->actingAs($manager)->getJson('/api/v1/reports/aging');

    $response->assertOk()
        ->assertJsonCount(4, 'data.buckets');
})->group('reports');


// ─────────────────────────────────────────────────────────────────────────
// tests/Feature/DashboardApiTest.php
// ─────────────────────────────────────────────────────────────────────────
uses(\Tests\CoopTestCase::class)->in('Feature');

it('dashboard endpoint returns all expected sections', function () {
    $officer  = $this->loanOfficer();
    $response = $this->actingAs($officer)->getJson('/api/v1/dashboard');

    $response->assertOk()
        ->assertJsonStructure(['data' => [
            'stats', 'monthly_collections', 'loan_status',
            'disbursements', 'loan_types', 'aging',
            'share_capital', 'recent_activity', 'top_overdue',
        ]]);
})->group('dashboard');

it('dashboard requires authentication', function () {
    $this->getJson('/api/v1/dashboard')->assertUnauthorized();
})->group('dashboard');


// ─────────────────────────────────────────────────────────────────────────
// tests/Feature/UserManagementTest.php
// ─────────────────────────────────────────────────────────────────────────

it('only super-admin can list users', function () {
    $this->actingAs($this->loanOfficer())->getJson('/api/v1/users')->assertForbidden();
    $this->actingAs($this->manager())->getJson('/api/v1/users')->assertForbidden();
    $this->actingAs($this->superAdmin())->getJson('/api/v1/users')->assertOk();
})->group('users');

it('super-admin can create a user', function () {
    $admin = $this->superAdmin();

    $response = $this->actingAs($admin)->postJson('/api/v1/users', [
        'name'     => 'Yang2x Rosales',
        'email'    => 'yang2x@crsecco.coop',
        'role'     => 'loan-officer',
        'password' => 'Coop2026!',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.role', 'loan-officer');
})->group('users');

it('super-admin cannot deactivate themselves', function () {
    $admin = $this->superAdmin();

    $this->actingAs($admin)
        ->postJson("/api/v1/users/{$admin->id}/toggle-active")
        ->assertForbidden();
})->group('users');

it('reset password returns a temp password and sets must_change_password', function () {
    $admin  = $this->superAdmin();
    $target = $this->loanOfficer();

    $response = $this->actingAs($admin)
        ->postJson("/api/v1/users/{$target->id}/reset-password");

    $response->assertOk()
        ->assertJsonStructure(['temp_password']);

    $this->assertDatabaseHas('users', [
        'id'                   => $target->id,
        'must_change_password' => true,
    ]);
})->group('users');


// ─────────────────────────────────────────────────────────────────────────
// tests/Feature/AuditLogTest.php
// ─────────────────────────────────────────────────────────────────────────

it('creating a member generates an audit log entry', function () {
    $officer = $this->loanOfficer();

    $this->actingAs($officer)->postJson('/api/v1/members', [
        'first_name'    => 'Test',
        'last_name'     => 'Audit',
        'member_no'     => 'M-AUDIT-001',
        'status'        => 'REGULAR',
        'member_status' => 'ACTIVE',
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'auditable_type' => 'App\Models\Member',
        'event'          => 'created',
        'user_id'        => $officer->id,
    ]);
})->group('audit');

it('only super-admin can view the audit log', function () {
    $this->actingAs($this->loanOfficer())->getJson('/api/v1/audit-logs')->assertForbidden();
    $this->actingAs($this->superAdmin())->getJson('/api/v1/audit-logs')->assertOk();
})->group('audit');


// ─────────────────────────────────────────────────────────────────────────
// tests/Unit/NotificationTest.php
// ─────────────────────────────────────────────────────────────────────────

use App\Services\NotificationService;
use App\Jobs\SendLoanNotification;
use Illuminate\Support\Facades\Queue;

it('loanApproved dispatches an SMS and email job when both channels are enabled', function () {
    Queue::fake();

    $loanType = $this->makeLoanType();
    $member   = $this->makeMember(['contact_number' => '09171234567', 'email' => 'test@crs.test']);
    $loan     = $this->makeActiveLoan($member, $loanType);

    app(NotificationService::class)->loanApproved($loan);

    Queue::assertPushed(SendLoanNotification::class, 2); // SMS + email
    Queue::assertPushed(SendLoanNotification::class, fn($job) => $job->channel === 'sms');
    Queue::assertPushed(SendLoanNotification::class, fn($job) => $job->channel === 'email');
})->group('notifications');

it('renders SMS template with member and loan details', function () {
    $loanType = $this->makeLoanType();
    $member   = $this->makeMember(['first_name' => 'Maria', 'last_name' => 'Santos']);
    $loan     = $this->makeActiveLoan($member, $loanType, ['amount' => 30000]);

    $service  = app(NotificationService::class);
    $message  = $service->renderTemplate('loan_approved', 'sms', [
        'member_name'   => "{$member->first_name} {$member->last_name}",
        'loan_no'       => $loan->loan_no,
        'amount'        => number_format(30000, 2),
        'coop_name'     => 'CRS ECCO',
    ]);

    expect($message)->toContain('Maria Santos');
    expect($message)->toContain($loan->loan_no);
    expect($message)->toContain('30,000.00');
})->group('notifications');
