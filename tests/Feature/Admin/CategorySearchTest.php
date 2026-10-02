<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategorySearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_search_filters_by_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Category::create(['name' => 'Prestasi', 'slug' => 'prestasi']);
        Category::create(['name' => 'Opini', 'slug' => 'opini']);

        $this->actingAs($admin)
            ->get('/admin/categories?q=Prest')
            ->assertInertia(fn ($page) => $page->has('categories.data', 1));
    }
}