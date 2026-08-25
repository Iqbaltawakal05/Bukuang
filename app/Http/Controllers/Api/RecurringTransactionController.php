<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StoreRecurringTransactionRequest;
use App\Http\Requests\Transaction\UpdateRecurringTransactionRequest;
use App\Http\Resources\RecurringTransactionResource;
use App\Models\RecurringTransaction;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecurringTransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $recurring = RecurringTransaction::with('category')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => RecurringTransactionResource::collection($recurring),
        ], Response::HTTP_OK);
    }

    public function store(StoreRecurringTransactionRequest $request): JsonResponse
    {
        $recurring = $this->transactionService->createRecurringTransaction($request->user(), $request->validated());

        return response()->json([
            'message' => 'Transaksi berulang berhasil ditambahkan.',
            'data' => new RecurringTransactionResource($recurring->load('category')),
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request, RecurringTransaction $recurringTransaction): JsonResponse
    {
        $this->authorize('view', $recurringTransaction);

        return response()->json([
            'data' => new RecurringTransactionResource($recurringTransaction->load('category')),
        ], Response::HTTP_OK);
    }

    public function update(UpdateRecurringTransactionRequest $request, RecurringTransaction $recurringTransaction): JsonResponse
    {
        $this->authorize('update', $recurringTransaction);

        $recurringTransaction->update($request->validated());

        return response()->json([
            'message' => 'Transaksi berulang berhasil diperbarui.',
            'data' => new RecurringTransactionResource($recurringTransaction->fresh('category')),
        ], Response::HTTP_OK);
    }

    public function destroy(Request $request, RecurringTransaction $recurringTransaction): JsonResponse
    {
        $this->authorize('delete', $recurringTransaction);

        $recurringTransaction->delete();

        return response()->json([
            'message' => 'Transaksi berulang berhasil dihapus.',
        ], Response::HTTP_OK);
    }
}
