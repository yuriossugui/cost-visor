<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Transaction::where('user_id', auth()->id())
            ->with('category')
            ->orderBy('date', 'desc');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('description')) {
            $query->where('description', 'like', '%' . $request->description . '%');
        }

        if ($request->filled('month')) {
            $query->whereYear('date', substr($request->month, 0, 4))
                ->whereMonth('date', substr($request->month, 5, 2));
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $perPage = $request->integer('per_page', 15);
        $transactions = $query->paginate($perPage);

        return response()->json($transactions);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'description' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'type' => ['required', 'in:RECEITA,DESPESA'],
        ]);

        $category = Category::where('user_id', auth()->id())->find($validated['category_id']);

        if (! $category) {
            return response()->json([
                'message' => 'Category not found or does not belong to user',
                'errors' => ['category_id' => ['The selected category is invalid.']],
            ], 422);
        }

        if ($category->type !== $validated['type']) {
            return response()->json([
                'message' => 'Category type does not match transaction type',
            ], 422);
        }

        $transaction = Transaction::create([
            'user_id' => auth()->id(),
            'category_id' => $validated['category_id'],
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'date' => $validated['date'],
            'type' => $validated['type'],
        ]);

        return response()->json($transaction->load('category'), 201);
    }

    public function show(int $id): JsonResponse
    {
        $transaction = Transaction::where('user_id', auth()->id())
            ->with('category')
            ->findOrFail($id);

        return response()->json($transaction);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $transaction = Transaction::where('user_id', auth()->id())->findOrFail($id);

        $validated = $request->validate([
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
            'amount' => ['sometimes', 'required', 'numeric', 'gt:0'],
            'description' => ['sometimes', 'required', 'string', 'max:255'],
            'date' => ['sometimes', 'required', 'date'],
            'type' => ['sometimes', 'required', 'in:RECEITA,DESPESA'],
        ]);

        if (isset($validated['category_id'])) {
            $category = Category::where('user_id', auth()->id())->find($validated['category_id']);

            if (! $category) {
                return response()->json([
                    'message' => 'Category not found or does not belong to user',
                    'errors' => ['category_id' => ['The selected category is invalid.']],
                ], 422);
            }

            $type = $validated['type'] ?? $transaction->type;

            if ($category->type !== $type) {
                return response()->json([
                    'message' => 'Category type does not match transaction type',
                ], 422);
            }
        }

        $transaction->update($validated);

        return response()->json($transaction->load('category'));
    }

    public function destroy(int $id): JsonResponse
    {
        $transaction = Transaction::where('user_id', auth()->id())->findOrFail($id);

        $transaction->delete();

        return response()->json(['message' => 'Transaction deleted successfully']);
    }
}