<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('documents:refresh-alarms', function () {
    app(\App\Services\EmployeeDocumentService::class)->refreshAlarms();
    $this->info('Document expiry alarms refreshed.');
})->purpose('Refresh employee document expiry alarms and onboarding eligibility');

\Illuminate\Support\Facades\Schedule::command('documents:refresh-alarms')->hourly()->withoutOverlapping();
