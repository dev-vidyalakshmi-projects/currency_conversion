<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\ReportData;
use App\Models\ReportRequest;
use App\Services\CurrencyLayerService;
use Carbon\Carbon;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateReportJob implements ShouldQueue
{
    use Queueable,Dispatchable, InteractsWithQueue, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(public ReportRequest $reportRequest)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(CurrencyLayerService $currencyService): void
    {
        //
         $report = $this->reportRequest;

        try {
            $report->update(['status' => ReportRequest::STATUS_PROCESSING]);

            $dates = $this->buildDateRange(
                $report->range,
                $report->interval
            );

            foreach ($dates as $date) {
                $rate = $currencyService->getHistoricalRate(
                    $report->currency_code,
                    $date->format('Y-m-d')
                );

                ReportData::create([
                    'report_request_id' => $report->id,
                    'date'              => $date->format('Y-m-d'),
                    'rate'              => $rate,
                ]);
            }

            $report->update(['status' => ReportRequest::STATUS_COMPLETED]);

            Log::info("Report #{$report->id} completed.", [
                'currency' => $report->currency_code,
                'entries'  => count($dates),
            ]);

        } catch (\Exception $e) {
            $report->update([
                'status'        => ReportRequest::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);
            Log::error("Report #{$report->id} failed: " . $e->getMessage());
            throw $e;
        }
    }

    private function buildDateRange(string $range, string $interval): array
    {
        $end   = Carbon::yesterday();
        $start = match ($range) {
            ReportRequest::RANGE_ONE_YEAR   => $end->copy()->subYear(),
            ReportRequest::RANGE_SIX_MONTHS => $end->copy()->subMonths(6),
            ReportRequest::RANGE_ONE_MONTH  => $end->copy()->subMonth(),
        };

        $dates   = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            $dates[]  = $current->copy();
            $current  = match ($interval) {
                ReportRequest::INTERVAL_MONTHLY => $current->addMonth(),
                ReportRequest::INTERVAL_WEEKLY  => $current->addWeek(),
                ReportRequest::INTERVAL_DAILY   => $current->addDay(),
            };
        }

        return $dates;
    }
}
