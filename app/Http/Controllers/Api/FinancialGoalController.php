<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FinancialGoal\StoreFinancialGoalRequest;
use App\Http\Requests\FinancialGoal\UpdateFinancialGoalRequest;
use App\Http\Resources\FinancialGoalResource;
use App\Models\FinancialGoal;
use App\Services\FinancialGoalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FinancialGoalController extends Controller
{
    public function __construct(
        protected FinancialGoalService $financialGoalService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status']);
        $goals = $this->financialGoalService->getFinancialGoalsForUser($request->user(), $filters);

        return response()->json([
            'data' => FinancialGoalResource::collection($goals),
        ], Response::HTTP_OK);
    }

    public function store(StoreFinancialGoalRequest $request): JsonResponse
    {
        $goal = $this->financialGoalService->createFinancialGoal($request->user(), $request->validated());

        return response()->json([
            'message' => 'Target keuangan berhasil dibuat.',
            'data' => new FinancialGoalResource($goal),
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request, FinancialGoal $financialGoal): JsonResponse
    {
        $this->authorize('view', $financialGoal);

        return response()->json([
            'data' => new FinancialGoalResource($financialGoal),
        ], Response::HTTP_OK);
    }

    public function update(UpdateFinancialGoalRequest $request, FinancialGoal $financialGoal): JsonResponse
    {
        $this->authorize('update', $financialGoal);

        $updatedGoal = $this->financialGoalService->updateFinancialGoal($financialGoal, $request->validated());

        return response()->json([
            'message' => 'Target keuangan berhasil diperbarui.',
            'data' => new FinancialGoalResource($updatedGoal),
        ], Response::HTTP_OK);
    }

    public function destroy(Request $request, FinancialGoal $financialGoal): JsonResponse
    {
        $this->authorize('delete', $financialGoal);

        $this->financialGoalService->deleteFinancialGoal($financialGoal);

        return response()->json([
            'message' => 'Target keuangan berhasil dihapus.',
        ], Response::HTTP_OK);
    }
}
