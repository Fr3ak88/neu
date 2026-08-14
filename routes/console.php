<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(\Illuminate\Foundation\Inspiring::quote());
})->purpose('Display an inspiring quote');

// FBA: Aktive Sendungen alle 5 Minuten bei Amazon prüfen
Schedule::command('fba:poll-shipments')->everyFiveMinutes();

// Rechnungen: Täglich überfällige Rechnungen prüfen
Schedule::command('invoices:check-overdue')->dailyAt('06:00');

// Rechnungen: Täglich wiederkehrende Aufträge verarbeiten
Schedule::command('invoices:process-recurring')->dailyAt('06:30');
