<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FinancialCalculationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected FinancialCalculationService $financialService;

    public function __construct(FinancialCalculationService $financialService)
    {
        $this->financialService = $financialService;
    }

    /**
     * Get dashboard summary for a specific month
     * Defaults to current month if not specified
     */
    public function index(Request $request): JsonResponse
    {
        $year = $request->integer('year', Carbon::now()->year);
        $month = $request->integer('month', Carbon::now()->month);

        // Validate month/year
        if ($month < 1 || $month > 12) {
            return response()->json([
                'message' => 'Invalid month. Must be between 1 and 12.',
            ], 422);
        }

        if ($year < 2000 || $year > 2100) {
            return response()->json([
                'message' => 'Invalid year.',
            ], 422);
        }

        $dashboard = $this->financialService->getDashboardData(auth()->id(), $year, $month);

        return response()->json([
            'period' => [
                'year' => $year,
                'month' => $month,
            ],
            'indicators' => [
                'balance' => $dashboard['balance'],
                'total_receitas' => $dashboard['total_receitas'],
                'total_despesas' => $dashboard['total_despesas'],
                'transaction_count' => $dashboard['transaction_count'],
            ],
            'expense_concentration' => $dashboard['expense_concentration'],
        ]);
    }

    /**
     * Get transactions for the dashboard period with pagination
     */
    public function transactions(Request $request): JsonResponse
    {
        $year = $request->integer('year', Carbon::now()->year);
        $month = $request->integer('month', Carbon::now()->month);
        $perPage = $request->integer('per_page', 20);

        if ($month < 1 || $month > 12) {
            return response()->json([
                'message' => 'Invalid month. Must be between 1 and 12.',
            ], 422);
        }

        $result = $this->financialService->getDashboardTransactions(auth()->id(), $year, $month, $perPage);

        return response()->json([
            'period' => [
                'year' => $year,
                'month' => $month,
            ],
            'transactions' => $result['data'],
            'pagination' => $result['pagination'],
        ]);
    }
}