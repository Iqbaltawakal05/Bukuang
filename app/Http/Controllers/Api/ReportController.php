<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    public function summary(Request $request): JsonResponse
    {
        $filters = $request->only(['period', 'start_date', 'end_date', 'type', 'category_id']);
        $report = $this->reportService->getSummaryReport($request->user(), $filters);

        return response()->json([
            'data' => $report,
        ], Response::HTTP_OK);
    }
}
