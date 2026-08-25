<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['type', 'category_id', 'start_date', 'end_date', 'search', 'per_page']);
        $transactions = $this->transactionService->getTransactionsForUser($request->user(), $filters);

        return response()->json([
            'data' => TransactionResource::collection($transactions),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ], Response::HTTP_OK);
    }

    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $transaction = $this->transactionService->createTransaction($request->user(), $request->validated());

        return response()->json([
            'message' => 'Transaksi berhasil dicatat.',
            'data' => new TransactionResource($transaction->load('category')),
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request, Transaction $transaction): JsonResponse
    {
        $this->authorize('view', $transaction);

        return response()->json([
            'data' => new TransactionResource($transaction->load('category')),
        ], Response::HTTP_OK);
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction): JsonResponse
    {
        $this->authorize('update', $transaction);

        $updatedTransaction = $this->transactionService->updateTransaction($transaction, $request->validated());

        return response()->json([
            'message' => 'Transaksi berhasil diperbarui.',
            'data' => new TransactionResource($updatedTransaction),
        ], Response::HTTP_OK);
    }

    public function destroy(Request $request, Transaction $transaction): JsonResponse
    {
        $this->authorize('delete', $transaction);

        $this->transactionService->deleteTransaction($transaction);

        return response()->json([
            'message' => 'Transaksi berhasil dihapus.',
        ], Response::HTTP_OK);
    }
}
