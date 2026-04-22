<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Jobs\GenerateReportJob;
use App\Models\ReportRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    $pending = ReportRequest::where('status', ReportRequest::STATUS_PENDING)
        ->get();

    Log::info("Scheduler: processing {$pending->count()} pending report(s).");

    foreach ($pending as $report) {
        GenerateReportJob::dispatch($report);
    }
})->everyFifteenMinutes()
  ->name('process-pending-reports')
  ->withoutOverlapping();
