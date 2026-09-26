<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::where('user_id', auth()->id())
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'type' => ['required', 'in:RECEITA,DESPESA'],
        ]);

        $category = Category::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'color' => $validated['color'],
            'type' => $validated['type'],
        ]);

        return response()->json($category, 201);
    }

    public function show(int $id): JsonResponse
    {
        $category = Category::where('user_id', auth()->id())->findOrFail($id);

        return response()->json($category);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = Category::where('user_id', auth()->id())->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'color' => ['sometimes', 'required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'type' => ['sometimes', 'required', 'in:RECEITA,DESPESA'],
        ]);

        $category->update($validated);

        return response()->json($category);
    }

    public function destroy(int $id): JsonResponse
    {
        $category = Category::where('user_id', auth()->id())->findOrFail($id);

        if ($category->transactions()->exists()) {
            return response()->json([
                'message' => 'Cannot delete category with associated transactions',
            ], 422);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}