<?php

namespace Database\Seeders;

use App\Models\LoanType;
use App\Models\Member;
use App\Models\Loan;
use App\Models\AmortizationSchedule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CoopSeeder extends Seeder
{
    public function run(): void
    {
        // ── Loan Types ────────────────────────────────────────────
        $loanTypes = [
            ['code' => 'commodity', 'label' => 'Commodity Loan',    'min_amount' => 10000, 'max_amount' => 100000, 'min_term' => 6,  'max_term' => 36, 'annual_rate_default' => 0.12, 'is_active' => true],
            ['code' => 'salary',    'label' => 'Salary / Cash Loan', 'min_amount' => 5000,  'max_amount' => 50000,  'min_term' => 3,  'max_term' => 24, 'annual_rate_default' => 0.12, 'is_active' => true],
            ['code' => 'emergency', 'label' => 'Emergency Loan',     'min_amount' => 3000,  'max_amount' => 30000,  'min_term' => 3,  'max_term' => 12, 'annual_rate_default' => 0.12, 'is_active' => true],
            ['code' => 'educ',      'label' => 'Educational Loan',   'min_amount' => 5000,  'max_amount' => 80000,  'min_term' => 6,  'max_term' => 24, 'annual_rate_default' => 0.10, 'is_active' => true],
            ['code' => 'multi',     'label' => 'Multi-purpose Loan', 'min_amount' => 10000, 'max_amount' => 150000, 'min_term' => 6,  'max_term' => 48, 'annual_rate_default' => 0.12, 'is_active' => true],
        ];

        foreach ($loanTypes as $lt) {
            LoanType::updateOrCreate(['code' => $lt['code']], $lt);
        }
        $this->command->info('✓ Loan types seeded');

        // ── Users ─────────────────────────────────────────────────
        $admin   = User::updateOrCreate(['email' => 'admin@crs.com'],   ['name' => 'System Admin',   'password' => Hash::make('crs2026')]);
        $officer = User::updateOrCreate(['email' => 'officer@crs.com'], ['name' => 'J. Monteverde',  'password' => Hash::make('crs2026')]);
        $staff   = User::updateOrCreate(['email' => 'staff@crs.com'],   ['name' => 'Records Staff',  'password' => Hash::make('crs2026')]);
        $this->command->info('✓ Users seeded (password: crs2026)');

        // ── Roles & Permissions ───────────────────────────────────
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view-member', 'create-member', 'edit-member', 'delete-member',
            'view-loan',   'create-loan',   'edit-loan',   'delete-loan',   'approve-loan',
        ];
        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $roleAdmin   = Role::firstOrCreate(['name' => 'super-admin',   'guard_name' => 'web']);
        $roleOfficer = Role::firstOrCreate(['name' => 'loan-officer',  'guard_name' => 'web']);
        $roleStaff   = Role::firstOrCreate(['name' => 'staff',         'guard_name' => 'web']);

        $roleAdmin->syncPermissions($permissions);
        $roleOfficer->syncPermissions(['view-member', 'view-loan', 'create-loan', 'edit-loan', 'approve-loan']);
        $roleStaff->syncPermissions(['view-member', 'create-member', 'edit-member', 'view-loan']);

        $admin->syncRoles([$roleAdmin]);
        $officer->syncRoles([$roleOfficer]);
        $staff->syncRoles([$roleStaff]);

        $this->command->info('✓ Roles & permissions seeded');

        // ── Members ───────────────────────────────────────────────
        $membersData = [
            [
                'member_no'      => 'CRS-00001',
                'last_name'      => 'Santos',
                'first_name'     => 'Maria',
                'middle_name'    => 'Reyes',
                'address'        => 'Blk 3 Lot 5 Mahogany St., Mandaue City, Cebu',
                'contact'        => '09171234501',
                'email'          => 'msantos@crsholdings.com',
                'company'        => 'CRS Holdings Corporation',
                'branch'         => 'Main – Mandaue',
                'department'     => 'Accounting & Finance',
                'status'         => 'REGULAR',
                'position'       => 'Senior Accountant',
                'supervisor'     => 'Roberto Tan',
                'date_hired'     => '2018-03-15',
                'monthly_salary' => 38500.00,
                'share_capital'  => 15000.00,
                'member_status'  => 'ACTIVE',
            ],
            [
                'member_no'      => 'CRS-00002',
                'last_name'      => 'dela Cruz',
                'first_name'     => 'Juan',
                'middle_name'    => 'Bautista',
                'address'        => '123 Osmena Blvd., Cebu City',
                'contact'        => '09281234502',
                'email'          => 'jdelacruz@crsholdings.com',
                'company'        => 'CRS Holdings Corporation',
                'branch'         => 'Main – Mandaue',
                'department'     => 'Information Technology',
                'status'         => 'REGULAR',
                'position'       => 'IT Specialist',
                'supervisor'     => 'Leilani Cruz',
                'date_hired'     => '2020-06-01',
                'monthly_salary' => 32000.00,
                'share_capital'  => 10000.00,
                'member_status'  => 'ACTIVE',
            ],
            [
                'member_no'      => 'CRS-00003',
                'last_name'      => 'Reyes',
                'first_name'     => 'Ana',
                'middle_name'    => 'Garcia',
                'address'        => '456 M.J. Cuenco Ave., Cebu City',
                'contact'        => '09351234503',
                'email'          => 'areyes@crsholdings.com',
                'company'        => 'CRS Holdings Corporation',
                'branch'         => 'South Branch',
                'department'     => 'Human Resources',
                'status'         => 'PROBI',
                'position'       => 'HR Assistant',
                'supervisor'     => 'Rosario Mendez',
                'date_hired'     => '2025-09-01',
                'monthly_salary' => 22000.00,
                'share_capital'  => 5000.00,
                'member_status'  => 'ACTIVE',
            ],
            [
                'member_no'      => 'CRS-00004',
                'last_name'      => 'Villanueva',
                'first_name'     => 'Pedro',
                'middle_name'    => 'Lopez',
                'address'        => '789 V. Rama Ave., Cebu City',
                'contact'        => '09191234504',
                'email'          => 'pvillanueva@crsholdings.com',
                'company'        => 'CRS Holdings Corporation',
                'branch'         => 'Main – Mandaue',
                'department'     => 'Operations',
                'status'         => 'REGULAR',
                'position'       => 'Operations Supervisor',
                'supervisor'     => 'Roberto Tan',
                'date_hired'     => '2016-01-10',
                'monthly_salary' => 45000.00,
                'share_capital'  => 25000.00,
                'member_status'  => 'ACTIVE',
            ],
            [
                'member_no'      => 'CRS-00005',
                'last_name'      => 'Mendoza',
                'first_name'     => 'Carla',
                'middle_name'    => 'Navarro',
                'address'        => '22 Salinas Dr., Lahug, Cebu City',
                'contact'        => '09061234505',
                'email'          => 'cmendoza@crsholdings.com',
                'company'        => 'CRS Holdings Corporation',
                'branch'         => 'North Branch',
                'department'     => 'Finance',
                'status'         => 'REGULAR',
                'position'       => 'Finance Manager',
                'supervisor'     => 'Eduardo Lim',
                'date_hired'     => '2014-08-22',
                'monthly_salary' => 55000.00,
                'share_capital'  => 35000.00,
                'member_status'  => 'ACTIVE',
            ],
        ];

        $members = [];
        foreach ($membersData as $m) {
            $members[] = Member::updateOrCreate(['member_no' => $m['member_no']], $m);
        }
        $this->command->info('✓ Members seeded: ' . count($members));

        // ── Loans + Amortization Schedules ────────────────────────
        $commodity = LoanType::where('code', 'commodity')->first();
        $salary    = LoanType::where('code', 'salary')->first();
        $emergency = LoanType::where('code', 'emergency')->first();
        $educ      = LoanType::where('code', 'educ')->first();
        $multi     = LoanType::where('code', 'multi')->first();

        $loansData = [
            [
                'loan_no'        => 'LN-2026-00001',
                'member'         => $members[0],
                'loan_type'      => $commodity,
                'amount'         => 50000.00,
                'term_months'    => 24,
                'frequency'      => 'bimonthly',
                'purpose'        => 'Home appliance purchase (refrigerator, washing machine)',
                'status'         => 'ACTIVE',
                'application_date' => '2026-01-10',
                'approval_date'  => '2026-01-12',
                'first_due_date' => '2026-02-01',
                'approved_by_hr'   => 'Rosario Mendez',
                'approved_by_coop' => 'J. Monteverde',
                'co_maker_idx'   => [1, 2],
            ],
            [
                'loan_no'        => 'LN-2026-00002',
                'member'         => $members[1],
                'loan_type'      => $salary,
                'amount'         => 25000.00,
                'term_months'    => 12,
                'frequency'      => 'monthly',
                'purpose'        => 'Personal cash loan for family needs',
                'status'         => 'PENDING',
                'application_date' => '2026-03-05',
                'co_maker_idx'   => [0],
            ],
            [
                'loan_no'        => 'LN-2026-00003',
                'member'         => $members[2],
                'loan_type'      => $emergency,
                'amount'         => 15000.00,
                'term_months'    => 6,
                'frequency'      => 'monthly',
                'purpose'        => 'Medical emergency – hospitalization',
                'status'         => 'DRAFT',
                'application_date' => '2026-04-01',
                'co_maker_idx'   => [],
            ],
            [
                'loan_no'        => 'LN-2026-00004',
                'member'         => $members[3],
                'loan_type'      => $educ,
                'amount'         => 40000.00,
                'term_months'    => 18,
                'frequency'      => 'bimonthly',
                'purpose'        => 'College tuition for dependent – 1st semester AY2026-2027',
                'status'         => 'APPROVED',
                'application_date' => '2026-02-20',
                'approval_date'  => '2026-02-24',
                'first_due_date' => '2026-03-15',
                'approved_by_hr'   => 'Rosario Mendez',
                'approved_by_coop' => 'J. Monteverde',
                'co_maker_idx'   => [0],
            ],
            [
                'loan_no'        => 'LN-2026-00005',
                'member'         => $members[4],
                'loan_type'      => $multi,
                'amount'         => 80000.00,
                'term_months'    => 12,
                'frequency'      => 'monthly',
                'purpose'        => 'Home improvement – roofing & painting',
                'status'         => 'CLOSED',
                'application_date' => '2025-01-05',
                'approval_date'  => '2025-01-08',
                'first_due_date' => '2025-02-05',
                'approved_by_hr'   => 'Rosario Mendez',
                'approved_by_coop' => 'J. Monteverde',
                'co_maker_idx'   => [1, 3],
            ],
        ];

        foreach ($loansData as $ld) {
            $calc = $this->computeSchedule(
                $ld['amount'],
                $ld['term_months'],
                $ld['frequency'],
                (float) $ld['loan_type']->annual_rate_default
            );

            $dueDates = !empty($ld['first_due_date'])
                ? $this->generateDueDates($ld['first_due_date'], $calc['n_periods'], $ld['frequency'])
                : array_fill(0, $calc['n_periods'], null);

            $coMaker1 = isset($ld['co_maker_idx'][0]) ? $members[$ld['co_maker_idx'][0]]->id : null;
            $coMaker2 = isset($ld['co_maker_idx'][1]) ? $members[$ld['co_maker_idx'][1]]->id : null;

            $loan = Loan::updateOrCreate(['loan_no' => $ld['loan_no']], [
                'member_id'         => $ld['member']->id,
                'loan_type_id'      => $ld['loan_type']->id,
                'amount'            => $ld['amount'],
                'term_months'       => $ld['term_months'],
                'frequency'         => $ld['frequency'],
                'annual_rate'       => $ld['loan_type']->annual_rate_default,
                'purpose'           => $ld['purpose'],
                'co_maker_1_id'     => $coMaker1,
                'co_maker_2_id'     => $coMaker2,
                'status'            => $ld['status'],
                'total_payment'     => $calc['total_payment'],
                'total_interest'    => $calc['total_interest'],
                'n_periods'         => $calc['n_periods'],
                'first_payment_amt' => $calc['first_payment'],
                'last_payment_amt'  => $calc['last_payment'],
                'application_date'  => $ld['application_date'],
                'approval_date'     => $ld['approval_date'] ?? null,
                'first_due_date'    => $ld['first_due_date'] ?? null,
                'end_date'          => !empty($dueDates) ? end($dueDates) : null,
                'approved_by_hr'    => $ld['approved_by_hr'] ?? null,
                'approved_by_coop'  => $ld['approved_by_coop'] ?? null,
            ]);

            // Rebuild amortization schedules
            AmortizationSchedule::where('loan_id', $loan->id)->delete();

            $schedules = [];
            foreach ($calc['schedule'] as $i => $row) {
                $isPaid = $ld['status'] === 'CLOSED';
                $schedules[] = [
                    'loan_id'    => $loan->id,
                    'period_no'  => $row['period'],
                    'due_date'   => $dueDates[$i] ?? null,
                    'principal'  => $row['principal'],
                    'interest'   => $row['interest'],
                    'amount_due' => $row['payment'],
                    'balance'    => $row['balance'],
                    'status'     => $isPaid ? 'PAID' : 'PENDING',
                    'paid_amount'=> $isPaid ? $row['payment'] : 0,
                    'paid_date'  => $isPaid ? ($dueDates[$i] ?? null) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            AmortizationSchedule::insert($schedules);
        }

        $this->command->info('✓ Loans seeded: ' . count($loansData));
        $this->command->info('✓ Amortization schedules seeded');
        $this->command->newLine();
        $this->command->info('═══════════════════════════════════');
        $this->command->info('  CREDENTIALS');
        $this->command->info('  admin@crs.com   / crs2026  (admin)');
        $this->command->info('  officer@crs.com / crs2026  (loan officer)');
        $this->command->info('  staff@crs.com   / crs2026  (staff)');
        $this->command->info('═══════════════════════════════════');
    }

    private function computeSchedule(float $principal, int $termMonths, string $frequency, float $annualRate): array
    {
        $monthlyRate = $annualRate / 12;

        [$periodsPerMonth, $periodRateFactor] = match ($frequency) {
            'bimonthly' => [2, 0.5],
            'weekly'    => [4, 0.25],
            default     => [1, 1.0],
        };

        $nPeriods           = $termMonths * $periodsPerMonth;
        $principalPerPeriod = $principal / $nPeriods;
        $schedule           = [];
        $remaining          = $principal;
        $totalInterest      = 0.0;

        for ($i = 0; $i < $nPeriods; $i++) {
            $interest  = $remaining * $monthlyRate * $periodRateFactor;
            $payment   = $principalPerPeriod + $interest;
            $balance   = max(0, $remaining - $principalPerPeriod);

            $schedule[] = [
                'period'    => $i + 1,
                'principal' => round($principalPerPeriod, 2),
                'interest'  => round($interest, 2),
                'payment'   => round($payment, 2),
                'balance'   => round($balance, 2),
            ];

            $remaining    -= $principalPerPeriod;
            $totalInterest += $interest;
        }

        return [
            'schedule'      => $schedule,
            'n_periods'     => $nPeriods,
            'total_interest'=> round($totalInterest, 2),
            'total_payment' => round($principal + $totalInterest, 2),
            'first_payment' => $schedule[0]['payment'],
            'last_payment'  => $schedule[$nPeriods - 1]['payment'],
        ];
    }

    private function generateDueDates(string $firstDate, int $nPeriods, string $frequency): array
    {
        $dates   = [];
        $current = Carbon::parse($firstDate);
        for ($i = 0; $i < $nPeriods; $i++) {
            $dates[] = $current->toDateString();
            match ($frequency) {
                'bimonthly' => $current->addDays(15),
                'weekly'    => $current->addWeek(),
                default     => $current->addMonth(),
            };
        }
        return $dates;
    }
}
