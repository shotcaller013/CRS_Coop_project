<?php
// config/semaphore.php
return [
    'api_key'     => env('SEMAPHORE_API_KEY', ''),
    'sender_name' => env('SEMAPHORE_SENDER_NAME', 'CRSECCO'),  // max 11 chars, no spaces
    'coop_name'   => env('COOP_FULL_NAME', 'CRS Employees Credit Cooperative'),
];

// ── .env — add these keys ────────────────────────────────────────
//
// # Semaphore SMS
// SEMAPHORE_API_KEY=your_semaphore_api_key_here
// SEMAPHORE_SENDER_NAME=CRSECCO
// COOP_FULL_NAME="CRS Employees Credit Cooperative"
//
// # Laravel Queue (use database driver for simplicity)
// QUEUE_CONNECTION=database
//
// # Mail (choose one)
// MAIL_MAILER=smtp
// MAIL_HOST=smtp.gmail.com
// MAIL_PORT=587
// MAIL_USERNAME=your_email@gmail.com
// MAIL_PASSWORD=your_app_password
// MAIL_ENCRYPTION=tls
// MAIL_FROM_ADDRESS=noreply@crsecco.coop
// MAIL_FROM_NAME="CRS Employees Credit Cooperative"
//
// ── Run after adding QUEUE_CONNECTION=database ──────────────────
// php artisan queue:table
// php artisan migrate
// php artisan queue:work --tries=3 --timeout=60
//
// ── For production — use supervisor to keep queue:work alive ────
// See: https://laravel.com/docs/queues#supervisor-configuration
