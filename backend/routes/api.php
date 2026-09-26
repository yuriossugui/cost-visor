<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InstallmentController;
use App\Http\Controllers\Api\RecurrenceController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth.jwt')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [AuthController::class, 'updateProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('transactions', TransactionController::class);
    Route::apiResource('installments', InstallmentController::class);
    Route::apiResource('recurrences', RecurrenceController::class);
    Route::post('recurrences/{recurrence}/generate', [RecurrenceController::class, 'generate']);

    Route::get('dashboard', [DashboardController::class, 'index']);
    Route::get('dashboard/transactions', [DashboardController::class, 'transactions']);
});