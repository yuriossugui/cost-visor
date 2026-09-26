<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Installment;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InstallmentController extends Controller
{
    public function index(): JsonResponse
    {
        $installments = Installment::where('user_id', auth()->id())
            ->with(['category', 'transactions'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($installments);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'total_amount' => ['required', 'numeric', 'gt:0'],
            'installment_count' => ['required', 'integer', 'min:2', 'max:60'],
            'first_installment_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
        ]);

        $category = Category::where('user_id', auth()->id())->find($validated['category_id']);

        if (! $category) {
            return response()->json([
                'message' => 'Category not found or does not belong to user',
                'errors' => ['category_id' => ['The selected category is invalid.']],
            ], 422);
        }

        if ($category->type !== 'DESPESA') {
            return response()->json([
                'message' => 'Installments are only allowed for expense categories',
            ], 422);
        }

        return DB::transaction(function () use ($validated, $category) {
            $installment = Installment::create([
                'user_id' => auth()->id(),
                'category_id' => $validated['category_id'],
                'total_amount' => $validated['total_amount'],
                'installment_count' => $validated['installment_count'],
                'first_installment_date' => $validated['first_installment_date'],
                'description' => $validated['description'],
            ]);

            $this->generateInstallmentTransactions($installment);

            return response()->json($installment->load(['category', 'transactions']), 201);
        });
    }

    public function show(int $id): JsonResponse
    {
        $installment = Installment::where('user_id', auth()->id())
            ->with(['category', 'transactions'])
            ->findOrFail($id);

        return response()->json($installment);
    }

    public function destroy(int $id): JsonResponse
    {
        $installment = Installment::where('user_id', auth()->id())->findOrFail($id);

        // Delete associated transactions
        $installment->transactions()->delete();

        $installment->delete();

        return response()->json(['message' => 'Installment deleted successfully']);
    }

    protected function generateInstallmentTransactions(Installment $installment): void
    {
        $totalAmount = $installment->total_amount;
        $count = $installment->installment_count;
        $firstDate = $installment->first_installment_date;

        // Calculate base amount per installment
        $baseAmount = floor(($totalAmount * 100) / $count) / 100;
        $remainder = round($totalAmount - ($baseAmount * $count), 2);

        $transactions = [];

        for ($i = 1; $i <= $count; $i++) {
            $amount = $baseAmount;
            // Add remainder to the last installment
            if ($i === $count) {
                $amount += $remainder;
            }

            $date = $firstDate->copy()->addMonths($i - 1);

            $transactions[] = [
                'user_id' => $installment->user_id,
                'category_id' => $installment->category_id,
                'installment_id' => $installment->id,
                'installment_number' => $i,
                'amount' => $amount,
                'description' => $installment->description . " (Parcela {$i}/{$count})",
                'date' => $date,
                'type' => 'DESPESA',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Transaction::insert($transactions);
    }
}