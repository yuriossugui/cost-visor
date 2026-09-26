<?php

namespace App\Services;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialCalculationService
{
    /**
     * Calculate balance for a user in a given period
     * balance = receitas - despesas
     */
    public function calculateBalance(int $userId, Carbon $startDate, Carbon $endDate): float
    {
        $receitas = $this->calculateTotalReceitas($userId, $startDate, $endDate);
        $despesas = $this->calculateTotalDespesas($userId, $startDate, $endDate);
        
        return round($receitas - $despesas, 2);
    }

    /**
     * Calculate total receitas for a user in a given period
     */
    public function calculateTotalReceitas(int $userId, Carbon $startDate, Carbon $endDate): float
    {
        return (float) Transaction::where('user_id', $userId)
            ->where('type', 'RECEITA')
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->sum('amount');
    }

    /**
     * Calculate total despesas for a user in a given period
     */
    public function calculateTotalDespesas(int $userId, Carbon $startDate, Carbon $endDate): float
    {
        return (float) Transaction::where('user_id', $userId)
            ->where('type', 'DESPESA')
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->sum('amount');
    }

    /**
     * Calculate transaction count for a user in a given period
     */
    public function calculateTransactionCount(int $userId, Carbon $startDate, Carbon $endDate): int
    {
        return Transaction::where('user_id', $userId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->count();
    }

    /**
     * Calculate expense concentration by category
     * percentual = (soma das despesas da categoria / soma total das despesas) × 100
     */
    public function calculateExpenseConcentrationByCategory(int $userId, Carbon $startDate, Carbon $endDate): array
    {
        $totalDespesas = $this->calculateTotalDespesas($userId, $startDate, $endDate);
        
        if ($totalDespesas == 0) {
            return [];
        }

        $categoryTotals = Transaction::where('user_id', $userId)
            ->where('type', 'DESPESA')
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->with('category')
            ->get()
            ->groupBy('category_id')
            ->map(function ($transactions) {
                return [
                    'category_id' => $transactions->first()->category_id,
                    'category_name' => $transactions->first()->category->name ?? 'Sem categoria',
                    'category_color' => $transactions->first()->category->color ?? '#6366f1',
                    'total' => $transactions->sum('amount'),
                ];
            })
            ->values();

        return $categoryTotals->map(function ($category) use ($totalDespesas) {
            $category['percentage'] = round(($category['total'] / $totalDespesas) * 100, 2);
            return $category;
        })->toArray();
    }

    /**
     * Get dashboard data for a user for a specific month
     */
    public function getDashboardData(int $userId, int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        return [
            'balance' => $this->calculateBalance($userId, $startDate, $endDate),
            'total_receitas' => $this->calculateTotalReceitas($userId, $startDate, $endDate),
            'total_despesas' => $this->calculateTotalDespesas($userId, $startDate, $endDate),
            'transaction_count' => $this->calculateTransactionCount($userId, $startDate, $endDate),
            'expense_concentration' => $this->calculateExpenseConcentrationByCategory($userId, $startDate, $endDate),
        ];
    }

    /**
     * Get transactions for dashboard listing
     */
    public function getDashboardTransactions(int $userId, int $year, int $month, int $perPage = 20): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $query = Transaction::where('user_id', $userId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->with('category')
            ->orderBy('date', 'desc');

        $transactions = $query->paginate($perPage);

        return [
            'data' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ];
    }
}