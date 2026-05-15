---
name: project-crs-coop-overview
description: Full module inventory, stack, file structure, and current implementation state of the CRS Coop Loan Management System
metadata:
  type: project
---

# CRS Coop Loan Management System

**Stack:** Laravel 11 backend (PHP), Vue 3 + Vite + Pinia frontend, MySQL 8+

**Architecture:** Laravel REST API (`backend/`) + Vue 3 SPA (`frontend/`) + static member portal (`crs-member-portal/`)

**Default DB:** `crs_coop`

## Implemented Modules (as of May 2026)

Admin frontend (Vue 3, pages/stores/services pattern):
- Dashboard (analytics, charts)
- Members & 201 file
- Loan Officer Desk, Pipeline, Monitoring, Eligibility Engine
- Billing & Remittance
- Payments (loan collections)
- Share Capital Ledger
- Member Beneficiaries
- Reports: Collection Summary, Aging, Outstanding Balance
- Audit Log
- Loan Restructuring
- Notifications (Semaphore SMS)
- PDF Loan Packet
- Settings: Coop Profile, Loan Types, Preferences, **Member Portal Access**
- User Management (users, roles — in Settings)

Member Portal (static HTML/JS, separate login):
- Member login with bearer token auth
- Dashboard: loans summary, payment history, share capital, beneficiaries

## Key Directories

```
backend/
  app/Http/Controllers/Api/   ← 17+ controllers
  app/Models/                 ← 20+ Eloquent models
  app/Services/               ← 18 service classes
  database/migrations/        ← 16 migration files
  routes/api.php              ← all API routes

frontend/
  src/pages/                  ← page components (pages pattern)
  src/stores/                 ← Pinia stores
  src/services/               ← API service layer
  src/composables/            ← useApi, useAuth, useCurrency, etc.
  src/components/             ← reusable components
  src/router/                 ← modular Vue Router
  public/crs-member-portal/   ← static member portal served by Vite

crs-member-portal/            ← standalone static member portal source
database/                     ← (reference) raw SQL schema files
```

## Member Portal Module (added May 15 2026)

Source: integrated from `crs-coop-loan-management-system/` reference folder.

**New files created:**
- `backend/database/migrations/2026_05_15_000001_create_member_portal_tables.php`
- `backend/app/Models/MemberPortalAccount.php`
- `backend/app/Models/MemberPortalSession.php`
- `backend/app/Models/MemberPortalAuditLog.php`
- `backend/app/Http/Controllers/Api/MemberPortalAccountController.php`
- `backend/app/Http/Controllers/Api/MemberPortalAuthController.php`
- `backend/app/Http/Controllers/Api/MemberPortalController.php`
- `crs-member-portal/` (copied from reference)
- `frontend/public/crs-member-portal/` (copied from reference)

**Updated files:**
- `backend/routes/api.php` — member portal routes added
- `frontend/src/services/setting.service.js` — portal account CRUD methods
- `frontend/src/stores/setting.store.js` — portalAccounts state + actions
- `frontend/src/pages/settings/SettingsPage.vue` — new "Member Portal Access" tab

**DB Tables:** `member_portal_accounts`, `member_portal_sessions`, `member_portal_audit_logs`

**Why:** How:** Member portal auth is separate from admin Sanctum auth — uses its own bearer token stored in `member_portal_sessions`. Admin manages accounts in Settings > Member Portal Access.

## Run Commands

```bash
# Backend (Laravel)
cd backend && php artisan migrate && php artisan serve

# Frontend (Vue)
cd frontend && npm run dev -- --host 0.0.0.0 --port 5174
```

**How to apply:** Use this to understand the codebase structure, which files own which features, and the overall architecture when making changes.
