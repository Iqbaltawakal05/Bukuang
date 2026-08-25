<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function summary(Request $request): JsonResponse
    {
        $summary = $this->dashboardService->getSummary($request->user());

        return response()->json([
            'data' => $summary,
        ], Response::HTTP_OK);
    }

    public function charts(Request $request): JsonResponse
    {
        $charts = $this->dashboardService->getCharts($request->user());

        return response()->json([
            'data' => $charts,
        ], Response::HTTP_OK);
    }
}
