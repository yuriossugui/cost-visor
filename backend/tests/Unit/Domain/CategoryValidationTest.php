<?php

namespace Tests\Unit\Domain;

use Tests\TestCase;
use App\Models\Category;

class CategoryValidationTest extends TestCase
{
    public function test_valid_category_data(): void
    {
        $category = new Category([
            'name' => 'Salary',
            'color' => '#6366f1',
            'type' => 'RECEITA',
        ]);

        $this->assertEquals('Salary', $category->name);
        $this->assertEquals('#6366f1', $category->color);
        $this->assertEquals('RECEITA', $category->type);
    }

    public function test_category_type_validation_via_database(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Category::create([
            'user_id' => 1,
            'name' => 'Test',
            'color' => '#6366f1',
            'type' => 'INVALID',
        ]);
    }
}