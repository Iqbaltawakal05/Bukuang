<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Budget\StoreBudgetRequest;
use App\Http\Requests\Budget\UpdateBudgetRequest;
use App\Http\Resources\BudgetResource;
use App\Models\Budget;
use App\Services\BudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BudgetController extends Controller
{
    public function __construct(
        protected BudgetService $budgetService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['month', 'year', 'category_id']);
        $budgets = $this->budgetService->getBudgetsForUser($request->user(), $filters);

        return response()->json([
            'data' => BudgetResource::collection($budgets),
        ], Response::HTTP_OK);
    }

    public function store(StoreBudgetRequest $request): JsonResponse
    {
        $budget = $this->budgetService->createBudget($request->user(), $request->validated());

        return response()->json([
            'message' => 'Budget berhasil dibuat.',
            'data' => new BudgetResource($budget->load('category')),
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request, Budget $budget): JsonResponse
    {
        $this->authorize('view', $budget);

        return response()->json([
            'data' => new BudgetResource($budget->load('category')),
        ], Response::HTTP_OK);
    }

    public function update(UpdateBudgetRequest $request, Budget $budget): JsonResponse
    {
        $this->authorize('update', $budget);

        $updatedBudget = $this->budgetService->updateBudget($budget, $request->validated());

        return response()->json([
            'message' => 'Budget berhasil diperbarui.',
            'data' => new BudgetResource($updatedBudget),
        ], Response::HTTP_OK);
    }

    public function destroy(Request $request, Budget $budget): JsonResponse
    {
        $this->authorize('delete', $budget);

        $this->budgetService->deleteBudget($budget);

        return response()->json([
            'message' => 'Budget berhasil dihapus.',
        ], Response::HTTP_OK);
    }
}
