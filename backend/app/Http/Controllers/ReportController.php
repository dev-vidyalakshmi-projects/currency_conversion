<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateReportJob;
use App\Models\ReportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $reports = $request->user()
            ->reportRequests()
            ->latest()
            ->get(['id', 'currency_code', 'range', 'interval',
                   'status', 'created_at', 'updated_at']);

        return response()->json(['reports' => $reports]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'currency_code' => ['required', 'string', 'size:3'],
            'range'         => ['required', Rule::in(array_keys(
                                    ReportRequest::validCombinations()))],
            'interval'      => ['required', Rule::in(array_values(
                                    ReportRequest::validCombinations()))],
        ]);

        $validCombinations = ReportRequest::validCombinations();
        if ($validCombinations[$request->range] !== $request->interval) {
            return response()->json([
                'message' => 'Invalid range and interval combination.',
            ], 422);
        }

        $report = ReportRequest::create([
            'user_id'       => $request->user()->id,
            'currency_code' => strtoupper($request->currency_code),
            'range'         => $request->range,
            'interval'      => $request->interval,
            'status'        => ReportRequest::STATUS_PENDING,
        ]);

        return response()->json([
            'message' => 'Report request submitted successfully.',
            'report'  => $report,
        ], 201);
    }

    public function show(Request $request, ReportRequest $reportRequest): JsonResponse
    {
        if ($reportRequest->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (!$reportRequest->isCompleted()) {
            return response()->json([
                'message' => 'Report is not yet completed.',
                'status'  => $reportRequest->status,
            ], 422);
        }

        $reportRequest->load('reportData');

        return response()->json([
            'report' => $reportRequest,
            'data'   => $reportRequest->reportData->map(fn($row) => [
                'date' => $row->date->format('Y-m-d'),
                'rate' => $row->rate,
            ]),
        ]);
    }
}
