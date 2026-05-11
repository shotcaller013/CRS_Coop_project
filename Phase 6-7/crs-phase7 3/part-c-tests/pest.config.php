<?php
// pest.php (project root — Pest configuration file)
uses(Tests\CoopTestCase::class)->in('tests/Unit', 'tests/Feature');

// ── Test groups ───────────────────────────────────────────────────────────
// Run all tests:           php artisan test
// Run critical tests only: php artisan test --group=critical
// Run amortization only:   php artisan test --group=amortization
// Run a single file:       php artisan test tests/Unit/AmortizationTest.php

// ── composer.json — add to require-dev ───────────────────────────────────
// "pestphp/pest": "^2.0",
// "pestphp/pest-plugin-laravel": "^2.0",

// ── Install Pest ─────────────────────────────────────────────────────────
// composer require --dev pestphp/pest pestphp/pest-plugin-laravel
// php artisan pest:install

// ── phpunit.xml — ensure test database is configured ─────────────────────
// Add to <php> section:
// <env name="DB_CONNECTION" value="sqlite"/>
// <env name="DB_DATABASE"   value=":memory:"/>
// <env name="CACHE_DRIVER"  value="array"/>
// <env name="QUEUE_CONNECTION" value="sync"/>
// <env name="MAIL_MAILER"   value="array"/>

// ── File placement guide ──────────────────────────────────────────────────
// tests/
//   Support/
//     CoopTestCase.php       ← base test class with role helpers
//   Unit/
//     AmortizationTest.php   ← CRITICAL — math verification
//     EligibilityTest.php    ← qualification rule checks
//     BeneficiaryTest.php    ← CDA compliance checks
//     RestructuringTest.php  ← restructuring engine
//     NotificationTest.php   ← notification dispatch (from AllOtherTests.php)
//   Feature/
//     MemberApiTest.php      ← member CRUD + policy
//     LoanApiTest.php        ← CRITICAL — loan lifecycle
//     PaymentApiTest.php     ← payment + auto-close
//     ReportApiTest.php      ← report auth guards
//     DashboardApiTest.php   ← dashboard endpoint
//     UserManagementTest.php ← user CRUD + guards
//     AuditLogTest.php       ← observer writes + auth
//
// Note: AllOtherTests.php contains multiple test suites separated by
// comments. Split these into individual files by cutting at each
// "tests/Feature/..." comment header. Or run them as-is — Pest
// handles multiple describe blocks in one file.

// ── Recommended CI command ────────────────────────────────────────────────
// php artisan test --parallel --group=critical  ← fast pre-deploy check
// php artisan test --parallel                   ← full suite
