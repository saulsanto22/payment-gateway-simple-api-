<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display acn inspiring quote');

Schedule::command('order:reminder-unpaid-order')->dailyAt('08:00')->withoutOverlapping();
