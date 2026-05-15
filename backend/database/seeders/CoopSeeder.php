<?php

namespace Database\Seeders;

use App\Models\Beneficiary;
use App\Models\LoanType;
use App\Models\Member;
use App\Models\MemberPortalAccount;
use App\Models\Loan;
use App\Models\AmortizationSchedule;
use App\Models\Payment;
use App\Models\ShareCapitalTransaction;
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

        // ── Member Portal Accounts ────────────────────────────────
        $portalPassword  = 'Demo@1234';
        $defaultModules  = ['dashboard', 'loans', 'payments', 'shareCapital', 'beneficiaries', 'profile'];

        foreach ($members as $member) {
            $username = strstr($member->email, '@', true); // e.g. msantos
            MemberPortalAccount::updateOrCreate(
                ['member_id' => $member->id],
                [
                    'username'              => $username,
                    'email'                 => $member->email,
                    'password_hash'         => Hash::make($portalPassword),
                    'force_password_change' => false,
                    'modules_json'          => $defaultModules,
                    'is_active'             => true,
                ]
            );
        }
        $this->command->info('✓ Member portal accounts seeded (password: ' . $portalPassword . ')');

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

        // Remove any stale loans for demo members that are NOT in the seeded set
        // (prevents leftover loan numbers from previous seeder runs polluting the portal)
        $demoMemberIds  = array_map(fn($m) => $m->id, $members);
        $seededLoanNos  = array_column($loansData, 'loan_no');
        $staleLoans = Loan::whereIn('member_id', $demoMemberIds)
            ->whereNotIn('loan_no', $seededLoanNos)
            ->get();
        foreach ($staleLoans as $stale) {
            Payment::where('loan_id', $stale->id)->delete();           // payments first (FK to schedules)
            AmortizationSchedule::where('loan_id', $stale->id)->forceDelete();
            try { $stale->forceDelete(); } catch (\Throwable) { $stale->delete(); }
        }
        if ($staleLoans->count()) {
            $this->command->warn("  Removed {$staleLoans->count()} stale loan(s) from previous seeder runs");
        }

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

            // Rebuild amortization schedules (delete payments first due to FK constraint)
            Payment::where('loan_id', $loan->id)->delete();
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

        // ── Payments ──────────────────────────────────────────────
        // Maria Santos – LN-2026-00001 (5 of 48 bimonthly periods paid)
        $mariaLoan = Loan::where('loan_no', 'LN-2026-00001')->first();
        if ($mariaLoan) {
            Payment::where('loan_id', $mariaLoan->id)->delete();
            $mariaSchedules = AmortizationSchedule::where('loan_id', $mariaLoan->id)
                ->orderBy('period_no')->take(5)->get();
            foreach ($mariaSchedules as $idx => $sched) {
                Payment::create([
                    'loan_id'      => $mariaLoan->id,
                    'schedule_id'  => $sched->id,
                    'amount_paid'  => $sched->amount_due,
                    'payment_type' => 'full',
                    'or_number'    => sprintf('OR-2026-%05d', 101 + $idx),
                    'payment_date' => $sched->due_date,
                    'penalty_paid' => 0,
                    'balance_after'=> $sched->balance,
                    'received_by'  => $officer->id,
                ]);
                $sched->update(['status' => 'PAID', 'paid_amount' => $sched->amount_due, 'paid_date' => $sched->due_date]);
            }
            // Mark remaining past-due schedules as OVERDUE
            AmortizationSchedule::where('loan_id', $mariaLoan->id)
                ->where('period_no', '>', 5)
                ->whereNotNull('due_date')
                ->where('due_date', '<', now()->toDateString())
                ->where('status', 'PENDING')
                ->update(['status' => 'OVERDUE']);
        }

        // Carla Mendoza – LN-2026-00005 (CLOSED, all 12 monthly periods)
        $carlaLoan = Loan::where('loan_no', 'LN-2026-00005')->first();
        if ($carlaLoan) {
            Payment::where('loan_id', $carlaLoan->id)->delete();
            $carlaSchedules = AmortizationSchedule::where('loan_id', $carlaLoan->id)
                ->orderBy('period_no')->get();
            foreach ($carlaSchedules as $idx => $sched) {
                Payment::create([
                    'loan_id'      => $carlaLoan->id,
                    'schedule_id'  => $sched->id,
                    'amount_paid'  => $sched->amount_due,
                    'payment_type' => 'full',
                    'or_number'    => sprintf('OR-2025-%05d', 201 + $idx),
                    'payment_date' => $sched->due_date,
                    'penalty_paid' => 0,
                    'balance_after'=> $sched->balance,
                    'received_by'  => $officer->id,
                ]);
            }
        }
        $this->command->info('✓ Payments seeded');

        // ── Share Capital Transactions ────────────────────────────
        ShareCapitalTransaction::whereIn('member_id', array_map(fn($m) => $m->id, $members))->forceDelete();

        $scData = [
            // Maria Santos – target ₱15,000
            ['member' => $members[0], 'rows' => [
                ['type' => 'opening', 'direction' => 'credit', 'amount' => 5000.00, 'balance_after' => 5000.00,  'date' => '2018-04-01', 'or' => 'SC-OR-18001', 'remarks' => 'Opening balance upon membership'],
                ['type' => 'deposit', 'direction' => 'credit', 'amount' => 3000.00, 'balance_after' => 8000.00,  'date' => '2020-01-10', 'or' => 'SC-OR-20001', 'remarks' => 'Annual share capital deposit'],
                ['type' => 'dividend','direction' => 'credit', 'amount' => 2000.00, 'balance_after' => 10000.00, 'date' => '2022-01-15', 'or' => 'SC-OR-22001', 'remarks' => 'Annual dividend 2021'],
                ['type' => 'deposit', 'direction' => 'credit', 'amount' => 3000.00, 'balance_after' => 13000.00, 'date' => '2024-01-10', 'or' => 'SC-OR-24001', 'remarks' => 'Annual share capital deposit'],
                ['type' => 'deposit', 'direction' => 'credit', 'amount' => 2000.00, 'balance_after' => 15000.00, 'date' => '2025-07-01', 'or' => 'SC-OR-25001', 'remarks' => 'Mid-year deposit'],
            ]],
            // Juan dela Cruz – target ₱10,000
            ['member' => $members[1], 'rows' => [
                ['type' => 'opening', 'direction' => 'credit', 'amount' => 3000.00, 'balance_after' => 3000.00,  'date' => '2020-07-01', 'or' => 'SC-OR-20002', 'remarks' => 'Opening balance upon membership'],
                ['type' => 'deposit', 'direction' => 'credit', 'amount' => 2000.00, 'balance_after' => 5000.00,  'date' => '2021-07-10', 'or' => 'SC-OR-21002', 'remarks' => 'Annual deposit'],
                ['type' => 'dividend','direction' => 'credit', 'amount' => 1000.00, 'balance_after' => 6000.00,  'date' => '2023-01-10', 'or' => 'SC-OR-23002', 'remarks' => 'Annual dividend 2022'],
                ['type' => 'deposit', 'direction' => 'credit', 'amount' => 2500.00, 'balance_after' => 8500.00,  'date' => '2024-07-05', 'or' => 'SC-OR-24002', 'remarks' => 'Annual deposit'],
                ['type' => 'deposit', 'direction' => 'credit', 'amount' => 1500.00, 'balance_after' => 10000.00, 'date' => '2025-07-01', 'or' => 'SC-OR-25002', 'remarks' => 'Mid-year deposit'],
            ]],
            // Ana Reyes – target ₱5,000
            ['member' => $members[2], 'rows' => [
                ['type' => 'opening', 'direction' => 'credit', 'amount' => 2000.00, 'balance_after' => 2000.00, 'date' => '2025-10-01', 'or' => 'SC-OR-25003', 'remarks' => 'Opening balance upon membership'],
                ['type' => 'deposit', 'direction' => 'credit', 'amount' => 1500.00, 'balance_after' => 3500.00, 'date' => '2026-01-05', 'or' => 'SC-OR-26003', 'remarks' => 'First quarterly deposit'],
                ['type' => 'deposit', 'direction' => 'credit', 'amount' => 1500.00, 'balance_after' => 5000.00, 'date' => '2026-04-01', 'or' => 'SC-OR-26004', 'remarks' => 'Second quarterly deposit'],
            ]],
            // Pedro Villanueva – target ₱25,000
            ['member' => $members[3], 'rows' => [
                ['type' => 'opening', 'direction' => 'credit', 'amount' => 5000.00, 'balance_after' => 5000.00,  'date' => '2016-02-01', 'or' => 'SC-OR-16004', 'remarks' => 'Opening balance upon membership'],
                ['type' => 'deposit', 'direction' => 'credit', 'amount' => 5000.00, 'balance_after' => 10000.00, 'date' => '2018-01-10', 'or' => 'SC-OR-18004', 'remarks' => 'Annual deposit'],
                ['type' => 'dividend','direction' => 'credit', 'amount' => 2500.00, 'balance_after' => 12500.00, 'date' => '2020-01-15', 'or' => 'SC-OR-20004', 'remarks' => 'Annual dividend 2019'],
                ['type' => 'deposit', 'direction' => 'credit', 'amount' => 5000.00, 'balance_after' => 17500.00, 'date' => '2022-01-10', 'or' => 'SC-OR-22004', 'remarks' => 'Annual deposit'],
                ['type' => 'dividend','direction' => 'credit', 'amount' => 2500.00, 'balance_after' => 20000.00, 'date' => '2024-01-15', 'or' => 'SC-OR-24004', 'remarks' => 'Annual dividend 2023'],
                ['type' => 'deposit', 'direction' => 'credit', 'amount' => 5000.00, 'balance_after' => 25000.00, 'date' => '2025-07-01', 'or' => 'SC-OR-25004', 'remarks' => 'Mid-year deposit'],
            ]],
            // Carla Mendoza – target ₱35,000
            ['member' => $members[4], 'rows' => [
                ['type' => 'opening', 'direction' => 'credit', 'amount' => 5000.00, 'balance_after' => 5000.00,  'date' => '2014-09-01', 'or' => 'SC-OR-14005', 'remarks' => 'Opening balance upon membership'],
                ['type' => 'deposit', 'direction' => 'credit', 'amount' => 5000.00, 'balance_after' => 10000.00, 'date' => '2016-01-10', 'or' => 'SC-OR-16005', 'remarks' => 'Annual deposit'],
                ['type' => 'dividend','direction' => 'credit', 'amount' => 2500.00, 'balance_after' => 12500.00, 'date' => '2018-01-15', 'or' => 'SC-OR-18005', 'remarks' => 'Annual dividend 2017'],
                ['type' => 'deposit', 'direction' => 'credit', 'amount' => 5000.00, 'balance_after' => 17500.00, 'date' => '2020-01-10', 'or' => 'SC-OR-20005', 'remarks' => 'Annual deposit'],
                ['type' => 'dividend','direction' => 'credit', 'amount' => 2500.00, 'balance_after' => 20000.00, 'date' => '2021-01-10', 'or' => 'SC-OR-21005', 'remarks' => 'Annual dividend 2020'],
                ['type' => 'deposit', 'direction' => 'credit', 'amount' => 7500.00, 'balance_after' => 27500.00, 'date' => '2023-01-10', 'or' => 'SC-OR-23005', 'remarks' => 'Annual deposit'],
                ['type' => 'deposit', 'direction' => 'credit', 'amount' => 5000.00, 'balance_after' => 32500.00, 'date' => '2025-01-10', 'or' => 'SC-OR-25005', 'remarks' => 'Annual deposit'],
                ['type' => 'dividend','direction' => 'credit', 'amount' => 2500.00, 'balance_after' => 35000.00, 'date' => '2026-01-10', 'or' => 'SC-OR-26005', 'remarks' => 'Annual dividend 2025'],
            ]],
        ];

        foreach ($scData as $memberSc) {
            foreach ($memberSc['rows'] as $row) {
                ShareCapitalTransaction::create([
                    'member_id'        => $memberSc['member']->id,
                    'type'             => $row['type'],
                    'direction'        => $row['direction'],
                    'amount'           => $row['amount'],
                    'balance_after'    => $row['balance_after'],
                    'or_number'        => $row['or'],
                    'transaction_date' => $row['date'],
                    'remarks'          => $row['remarks'],
                    'posted_by'        => $officer->id,
                ]);
            }
        }
        $this->command->info('✓ Share capital transactions seeded');

        // ── Beneficiaries ─────────────────────────────────────────
        Beneficiary::whereIn('member_id', array_map(fn($m) => $m->id, $members))->forceDelete();

        $beneficiaryData = [
            // Maria Santos
            ['member' => $members[0], 'rows' => [
                ['type' => 'primary',   'first_name' => 'Manuel',   'last_name' => 'Santos',   'middle_name' => 'Cruz',    'relationship' => 'Spouse',  'birthdate' => '1978-05-12', 'share_percentage' => 60.00, 'contact_number' => '09171234510', 'sort_order' => 1],
                ['type' => 'primary',   'first_name' => 'Gabriela', 'last_name' => 'Santos',   'middle_name' => null,      'relationship' => 'Daughter','birthdate' => '2008-03-20', 'share_percentage' => 40.00, 'contact_number' => null,          'sort_order' => 2,
                 'guardian_name' => 'Manuel Santos', 'guardian_contact' => '09171234510', 'guardian_relationship' => 'Father'],
            ]],
            // Juan dela Cruz
            ['member' => $members[1], 'rows' => [
                ['type' => 'primary',   'first_name' => 'Lina',     'last_name' => 'dela Cruz','middle_name' => 'Perez',   'relationship' => 'Spouse',  'birthdate' => '1987-08-15', 'share_percentage' => 100.00,'contact_number' => '09281234520', 'sort_order' => 1],
            ]],
            // Ana Reyes
            ['member' => $members[2], 'rows' => [
                ['type' => 'primary',   'first_name' => 'Eduardo',  'last_name' => 'Reyes',    'middle_name' => 'Santos',  'relationship' => 'Father',  'birthdate' => '1960-04-20', 'share_percentage' => 50.00, 'contact_number' => '09351234530', 'sort_order' => 1],
                ['type' => 'secondary', 'first_name' => 'Carmen',   'last_name' => 'Reyes',    'middle_name' => 'Garcia',  'relationship' => 'Mother',  'birthdate' => '1963-09-15', 'share_percentage' => 50.00, 'contact_number' => '09351234531', 'sort_order' => 1],
            ]],
            // Pedro Villanueva
            ['member' => $members[3], 'rows' => [
                ['type' => 'primary',   'first_name' => 'Elena',    'last_name' => 'Villanueva','middle_name'=> 'Soriano', 'relationship' => 'Spouse',  'birthdate' => '1980-11-08', 'share_percentage' => 60.00, 'contact_number' => '09191234540', 'sort_order' => 1],
                ['type' => 'primary',   'first_name' => 'Marco',    'last_name' => 'Villanueva','middle_name'=> null,      'relationship' => 'Son',     'birthdate' => '2005-06-25', 'share_percentage' => 40.00, 'contact_number' => null,          'sort_order' => 2,
                 'guardian_name' => 'Elena Villanueva', 'guardian_contact' => '09191234540', 'guardian_relationship' => 'Mother'],
            ]],
            // Carla Mendoza
            ['member' => $members[4], 'rows' => [
                ['type' => 'primary',   'first_name' => 'Ricardo',  'last_name' => 'Mendoza',  'middle_name' => 'Fuentes', 'relationship' => 'Spouse',  'birthdate' => '1975-03-10', 'share_percentage' => 60.00, 'contact_number' => '09061234550', 'sort_order' => 1],
                ['type' => 'secondary', 'first_name' => 'Isabella', 'last_name' => 'Mendoza',  'middle_name' => null,      'relationship' => 'Daughter','birthdate' => '2003-11-28', 'share_percentage' => 40.00, 'contact_number' => null,          'sort_order' => 1],
            ]],
        ];

        foreach ($beneficiaryData as $memberBen) {
            foreach ($memberBen['rows'] as $row) {
                Beneficiary::create([
                    'member_id'             => $memberBen['member']->id,
                    'type'                  => $row['type'],
                    'first_name'            => $row['first_name'],
                    'last_name'             => $row['last_name'],
                    'middle_name'           => $row['middle_name'] ?? null,
                    'relationship'          => $row['relationship'],
                    'birthdate'             => $row['birthdate'],
                    'share_percentage'      => $row['share_percentage'],
                    'contact_number'        => $row['contact_number'] ?? null,
                    'sort_order'            => $row['sort_order'],
                    'guardian_name'         => $row['guardian_name'] ?? null,
                    'guardian_contact'      => $row['guardian_contact'] ?? null,
                    'guardian_relationship' => $row['guardian_relationship'] ?? null,
                ]);
            }
        }
        $this->command->info('✓ Beneficiaries seeded');

        $this->command->newLine();
        $this->command->info('══════════════════════════════════════════════════════');
        $this->command->info('  ADMIN / STAFF  (login at /login)');
        $this->command->info('  admin@crs.com   / crs2026  (super-admin)');
        $this->command->info('  officer@crs.com / crs2026  (loan-officer)');
        $this->command->info('  staff@crs.com   / crs2026  (staff)');
        $this->command->info('──────────────────────────────────────────────────────');
        $this->command->info('  MEMBER PORTAL  (login at /portal)  pw: Demo@1234');
        foreach ($members as $member) {
            $username = strstr($member->email, '@', true);
            $this->command->info(sprintf(
                '  %-18s %-12s  %s %s',
                $username,
                $member->member_no,
                $member->first_name,
                $member->last_name
            ));
        }
        $this->command->info('══════════════════════════════════════════════════════');
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
