<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Recurrence;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecurrenceController extends Controller
{
    public function index(): JsonResponse
    {
        $recurrences = Recurrence::where('user_id', auth()->id())
            ->with('category')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($recurrences);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'description' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:RECEITA,DESPESA'],
            'frequency' => ['required', 'in:daily,weekly,monthly,yearly'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_business_day' => ['sometimes', 'boolean'],
            'business_day_number' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:5'],
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
                'message' => 'Category type does not match recurrence type',
            ], 422);
        }

        // Validate business_day_number only for monthly/yearly
        if (isset($validated['business_day_number']) && !in_array($validated['frequency'], ['monthly', 'yearly'])) {
            return response()->json([
                'message' => 'business_day_number is only allowed for monthly or yearly frequencies',
            ], 422);
        }

        if (isset($validated['is_business_day']) && $validated['is_business_day'] && !isset($validated['business_day_number']) && in_array($validated['frequency'], ['monthly', 'yearly'])) {
            return response()->json([
                'message' => 'business_day_number is required when is_business_day is true for monthly/yearly frequencies',
            ], 422);
        }

        $recurrence = Recurrence::create([
            'user_id' => auth()->id(),
            'category_id' => $validated['category_id'],
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'type' => $validated['type'],
            'frequency' => $validated['frequency'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'is_business_day' => $validated['is_business_day'] ?? false,
            'business_day_number' => $validated['business_day_number'] ?? null,
        ]);

        return response()->json($recurrence->load('category'), 201);
    }

    public function show(int $id): JsonResponse
    {
        $recurrence = Recurrence::where('user_id', auth()->id())
            ->with('category')
            ->findOrFail($id);

        return response()->json($recurrence);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $recurrence = Recurrence::where('user_id', auth()->id())->findOrFail($id);

        $validated = $request->validate([
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
            'amount' => ['sometimes', 'required', 'numeric', 'gt:0'],
            'description' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', 'in:RECEITA,DESPESA'],
            'frequency' => ['sometimes', 'required', 'in:daily,weekly,monthly,yearly'],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_business_day' => ['sometimes', 'boolean'],
            'business_day_number' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:5'],
        ]);

        if (isset($validated['category_id'])) {
            $category = Category::where('user_id', auth()->id())->findOrFail($validated['category_id']);
            $type = $validated['type'] ?? $recurrence->type;

            if ($category->type !== $type) {
                return response()->json([
                    'message' => 'Category type does not match recurrence type',
                ], 422);
            }
        }

        // Validate business_day_number only for monthly/yearly
        $frequency = $validated['frequency'] ?? $recurrence->frequency;
        if (isset($validated['business_day_number']) && !in_array($frequency, ['monthly', 'yearly'])) {
            return response()->json([
                'message' => 'business_day_number is only allowed for monthly or yearly frequencies',
            ], 422);
        }

        $isBusinessDay = $validated['is_business_day'] ?? $recurrence->is_business_day;
        if ($isBusinessDay && !isset($validated['business_day_number']) && $recurrence->business_day_number === null && in_array($frequency, ['monthly', 'yearly'])) {
            return response()->json([
                'message' => 'business_day_number is required when is_business_day is true for monthly/yearly frequencies',
            ], 422);
        }

        $recurrence->update($validated);

        return response()->json($recurrence->load('category'));
    }

    public function destroy(int $id): JsonResponse
    {
        $recurrence = Recurrence::where('user_id', auth()->id())->findOrFail($id);

        $recurrence->delete();

        return response()->json(['message' => 'Recurrence deleted successfully']);
    }

    public function generate(int $id): JsonResponse
    {
        $recurrence = Recurrence::where('user_id', auth()->id())->findOrFail($id);

        $until = request()->has('until') ? Carbon::parse(request()->input('until')) : null;

        $count = $recurrence->generateTransactions($until);

        return response()->json([
            'message' => 'Transactions generated successfully',
            'generated_count' => $count,
        ]);
    }
}