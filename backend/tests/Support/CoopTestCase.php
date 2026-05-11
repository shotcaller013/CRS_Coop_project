<?php
// tests/CoopTestCase.php
namespace Tests;

use App\Models\Loan;
use App\Models\LoanType;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class CoopTestCase extends TestCase
{
    use RefreshDatabase;

    // ── User helpers ─────────────────────────────────────────

    protected function superAdmin(): User
    {
        return $this->makeUser('super-admin');
    }

    protected function manager(): User
    {
        return $this->makeUser('manager');
    }

    protected function loanOfficer(): User
    {
        return $this->makeUser('loan-officer');
    }

    protected function staff(): User
    {
        return $this->makeUser('staff');
    }

    private function makeUser(string $role): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);
        return $user;
    }

    // ── Auth helper ──────────────────────────────────────────

    protected function actingAs(User $user): static
    {
        parent::actingAs($user, 'sanctum');
        return $this;
    }

    // ── Fixture helpers ──────────────────────────────────────

    protected function makeLoanType(array $overrides = []): LoanType
    {
        return LoanType::factory()->create(array_merge([
            'label'                 => 'Commodity',
            'code'                  => 'COM',
            'min_amount'            => 5000,
            'max_amount'            => 100000,
            'amount_cap_method'     => 'fixed',
            'salary_multiplier'     => null,
            'annual_rate_default'   => 12.00,
            'annual_rate_min'       => 10.00,
            'annual_rate_max'       => 18.00,
            'allowed_emp_statuses'  => json_encode(['REGULAR', 'PROBI']),
            'min_share_capital'     => 0,
            'min_tenure_months'     => 0,
            'allow_concurrent'      => false,
            'penalty_rate'          => 2.00,
            'is_active'             => true,
        ], $overrides));
    }

    protected function makeMember(array $overrides = []): Member
    {
        return Member::factory()->create(array_merge([
            'status'          => 'REGULAR',
            'member_status'   => 'ACTIVE',
            'monthly_salary'  => 25000,
            'share_capital'   => 5000,
            'date_hired'      => now()->subYears(2)->toDateString(),
        ], $overrides));
    }

    protected function makeActiveLoan(Member $member, LoanType $loanType, array $overrides = []): Loan
    {
        $loan = Loan::factory()->create(array_merge([
            'member_id'        => $member->id,
            'loan_type_id'     => $loanType->id,
            'amount'           => 60000,
            'term_months'      => 12,
            'frequency'        => 'MONTHLY',
            'annual_rate'      => 12.00,
            'status'           => 'ACTIVE',
            'application_date' => now()->toDateString(),
            'first_due_date'   => now()->addMonth()->toDateString(),
        ], $overrides));

        // Generate amortization schedule
        app(\App\Services\LoanService::class)->generateSchedule($loan);

        return $loan->fresh();
    }
}
