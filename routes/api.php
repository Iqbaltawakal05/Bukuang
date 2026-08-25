<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FinancialGoalController;
use App\Http\Controllers\Api\RecurringTransactionController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Bukuang (v1)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Public Authentication Routes (Rate Limited against Brute-Force)
    Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    // Protected Routes (Sanctum Authenticated)
    Route::middleware('auth:sanctum')->group(function () {
        // Auth Management
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
        });

        // Profile Management
        Route::prefix('profile')->group(function () {
            Route::get('/', [AuthController::class, 'profile']);
            Route::put('/', [AuthController::class, 'updateProfile']);
            Route::put('/password', [AuthController::class, 'updatePassword']);
        });

        // Category Management
        Route::apiResource('categories', CategoryController::class);

        // Transaction Management
        Route::apiResource('transactions', TransactionController::class);

        // Recurring Transaction Management
        Route::apiResource('recurring-transactions', RecurringTransactionController::class);

        // Budget Management
        Route::apiResource('budgets', BudgetController::class);

        // Financial Goals Management
        Route::post('financial-goals/{financial_goal}/contributions', [FinancialGoalController::class, 'storeContribution']);
        Route::apiResource('financial-goals', FinancialGoalController::class);

        // Dashboard Analytics
        Route::prefix('dashboard')->group(function () {
            Route::get('/summary', [DashboardController::class, 'summary']);
            Route::get('/charts', [DashboardController::class, 'charts']);
        });

        // Reports Analytics
        Route::prefix('reports')->group(function () {
            Route::get('/summary', [ReportController::class, 'summary']);
        });

        // Export Management
        Route::get('exports/{export}/download', [\App\Http\Controllers\Api\ExportController::class, 'download']);
        Route::apiResource('exports', \App\Http\Controllers\Api\ExportController::class)->except(['update', 'destroy']);
    });
});



