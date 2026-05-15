# CRS COOP — Laravel 11 + Vue 3 Module Generation
## Matches IT Team Format: Cooperative_System_Format.txt

---

## GENERATED MODULES

### Module 1: Member
### Module 2: Loan (with Amortization Schedule)
### Module 3: Member Portal (added May 2026)

Member portal allows coop members to log in via a separate static HTML/JS dashboard to view their loans, payments, share capital, and beneficiaries.

**Files added:**
- `crs-member-portal/` — standalone static portal (login + dashboard HTML/JS/CSS)
- `frontend/public/crs-member-portal/` — same files served by Vite for same-origin demo
- `backend/database/migrations/2026_05_15_000001_create_member_portal_tables.php` — 3 new DB tables
- `backend/app/Models/MemberPortalAccount.php` — portal account model
- `backend/app/Models/MemberPortalSession.php` — token session model
- `backend/app/Models/MemberPortalAuditLog.php` — audit log model
- `backend/app/Http/Controllers/Api/MemberPortalAccountController.php` — admin CRUD (Settings)
- `backend/app/Http/Controllers/Api/MemberPortalAuthController.php` — member login endpoint
- `backend/app/Http/Controllers/Api/MemberPortalController.php` — member dashboard data endpoint
- `frontend/src/services/setting.service.js` — extended with portal account methods
- `frontend/src/stores/setting.store.js` — extended with portal account state
- `frontend/src/pages/settings/SettingsPage.vue` — new "Member Portal Access" tab

**Setup:**
```bash
php artisan migrate
```

**Member Portal Endpoints (no Sanctum — token-based):**
```
POST /api/v1/member-portal/auth/login     ← member login
GET  /api/v1/member-portal/dashboard      ← member dashboard data (Bearer token)
```

**Admin Endpoints (Sanctum required):**
```
GET/POST                /api/v1/member-portal-accounts
GET/PUT                 /api/v1/member-portal-accounts/{id}
POST                    /api/v1/member-portal-accounts/{id}/toggle-active
POST                    /api/v1/member-portal-accounts/{id}/reset-password
```

**Local demo URL:**
```
http://localhost:5174/crs-member-portal/index.html
```

---

## BACKEND FILES (Laravel 11)

### Migrations (copy to: database/migrations/)
```
2026_01_01_000001_create_members_table.php
2026_01_01_000002_create_loan_types_table.php
2026_01_01_000003_create_loans_table.php
2026_01_01_000004_create_amortization_schedules_table.php
```

### Models (copy to: app/Models/)
```
Member.php
Loan.php
LoanType.php
AmortizationSchedule.php
```

### Policies (copy to: app/Policies/)
```
MemberPolicy.php         → permissions: view/create/edit/delete-member
LoanPolicy.php           → permissions: view/create/edit/delete/approve-loan
```

### Form Requests
```
app/Http/Requests/Member/StoreMemberRequest.php
app/Http/Requests/Member/UpdateMemberRequest.php
app/Http/Requests/Loan/StoreLoanRequest.php
app/Http/Requests/Loan/UpdateLoanRequest.php
```

### API Resources
```
app/Http/Resources/MemberResource.php
app/Http/Resources/MemberCollection.php
app/Http/Resources/LoanResource.php
app/Http/Resources/LoanCollection.php
app/Http/Resources/LoanTypeResource.php
app/Http/Resources/AmortizationScheduleResource.php
```

### Services
```
app/Services/MemberService.php
app/Services/LoanService.php      ← includes full amortization calculator
```

### Controllers (copy to: app/Http/Controllers/Api/)
```
MemberController.php
LoanController.php
```

### Providers
```
app/Providers/AuthServiceProvider.php   ← policy registration
```

### Routes (paste into: routes/api.php)
```
routes_api_snippet.php
```

### Seeder (copy to: database/seeders/)
```
database/seeders/CoopSeeder.php         ← loan types, permissions, roles
```

---

## FRONTEND FILES (Vue 3 + PrimeVue + Pinia)

### Pinia Stores
```
src/stores/member.store.js
src/stores/loan.store.js
```

### Services (Axios)
```
src/services/api.js            ← base Axios instance (already in project)
src/services/member.service.js
src/services/loan.service.js
```

### Composables
```
src/composables/useCurrency.js
src/composables/useDate.js
src/composables/useToast.js
```

### Common Components
```
src/components/common/StatusBadge.vue
src/components/common/InfoField.vue
```

### Member Components + Pages
```
src/components/member/MemberForm.vue
src/components/member/MemberTable.vue
src/pages/member/MemberListPage.vue
src/pages/member/MemberCreatePage.vue
src/pages/member/MemberEditPage.vue
src/pages/member/MemberDetailPage.vue
```

### Loan Components + Pages
```
src/components/loan/LoanForm.vue
src/components/loan/LoanTable.vue
src/pages/loan/LoanListPage.vue
src/pages/loan/LoanCreatePage.vue
src/pages/loan/LoanEditPage.vue
src/pages/loan/LoanDetailPage.vue
src/pages/loan/LoanPipelinePage.vue
```

### Routes
```
src/router/routes/member.routes.js
src/router/routes/loan.routes.js
```

---

## SETUP STEPS

### 1. Run migrations
```bash
php artisan migrate
```

### 2. Run seeder
```bash
php artisan db:seed --class=CoopSeeder
```

### 3. Register policies
Edit `app/Providers/AuthServiceProvider.php` — add from the generated file.

### 4. Add routes
Paste `routes_api_snippet.php` contents into `routes/api.php`.

### 5. Register routes in Vue Router
```js
// src/router/index.js
import { memberRoutes } from './routes/member.routes'
import { loanRoutes }   from './routes/loan.routes'

const routes = [
  ...memberRoutes,
  ...loanRoutes,
  // ... your other routes
]
```

### 6. Register PrimeVue components (if not globally registered)
```js
// src/main.js
import PrimeVue    from 'primevue/config'
import ToastService from 'primevue/toastservice'
import ConfirmationService from 'primevue/confirmationservice'
import Tooltip     from 'primevue/tooltip'

app.use(PrimeVue, { ripple: true })
app.use(ToastService)
app.use(ConfirmationService)
app.directive('tooltip', Tooltip)
```

---

## API ENDPOINTS GENERATED

### Members
| Method | Endpoint                  | Description            |
|--------|---------------------------|------------------------|
| GET    | /api/v1/members           | Paginated list         |
| POST   | /api/v1/members           | Create member          |
| GET    | /api/v1/members/{id}      | Show member            |
| PUT    | /api/v1/members/{id}      | Update member          |
| DELETE | /api/v1/members/{id}      | Soft delete            |
| GET    | /api/v1/members/dropdown  | Flat list for selects  |

### Loans
| Method | Endpoint                       | Description             |
|--------|--------------------------------|-------------------------|
| GET    | /api/v1/loans                  | Paginated list          |
| POST   | /api/v1/loans                  | Create + save schedule  |
| GET    | /api/v1/loans/{id}             | Show loan + schedule    |
| PUT    | /api/v1/loans/{id}             | Update loan             |
| DELETE | /api/v1/loans/{id}             | Soft delete             |
| POST   | /api/v1/loans/{id}/approve     | Approve loan            |
| GET    | /api/v1/loans/pipeline         | Pipeline grouped        |
| POST   | /api/v1/loans/calculate        | Calc-only (no save)     |
| GET    | /api/v1/loan-types             | Active loan types       |

---

## PERMISSIONS (Spatie)
```
view-member    create-member    edit-member    delete-member
view-loan      create-loan      edit-loan      delete-loan    approve-loan
```

## ROLES
```
super-admin   → all permissions
loan-officer  → view/create/edit members + view/create/edit/approve loans
staff         → view members + view loans
```

---

## LOAN MATH — Diminishing Balance
Matches CRS sample: ₱60,000 / 36 months / Bi-Monthly
- Period 1:  ₱833.33 + ₱600.00 = **₱1,433.33** ✓
- Period 72: ₱833.33 + ₱16.67  = **₱850.00** ✓

Implemented in: `LoanService::computeSchedule()` (PHP) and mirrored in `LoanForm.vue` via store `calculate()` API call.
