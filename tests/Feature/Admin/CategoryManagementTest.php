<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin   = User::factory()->create(['role' => 'admin']);
        $this->student = User::factory()->create(['role' => 'user']);
    }

    private function category(string $name): Category
    {
        return Category::create(['name' => $name, 'slug' => \Illuminate\Support\Str::slug($name)]);
    }

    public function test_admin_can_create_category(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/categories', ['name' => 'Prestasi Siswa'])
            ->assertSessionHasNoErrors();

        $this->assertSame('prestasi-siswa', Category::where('name', 'Prestasi Siswa')->value('slug'));
    }

    public function test_duplicate_name_is_rejected(): void
    {
        $this->category('Pengumuman');

        $this->actingAs($this->admin)
            ->post('/admin/categories', ['name' => 'Pengumuman'])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Category::count());
    }

    public function test_admin_can_rename_category(): void
    {
        $cat = $this->category('Opini');

        $this->actingAs($this->admin)
            ->put("/admin/categories/{$cat->id}", ['name' => 'Opini Siswa'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Opini Siswa', $cat->fresh()->name);
        $this->assertSame('opini-siswa', $cat->fresh()->slug);
    }

    public function test_cannot_delete_category_in_use(): void
    {
        $cat = $this->category('Info Lomba');
        Post::create([
            'user_id'     => $this->student->id,
            'category_id' => $cat->id,
            'title'       => 'Judul',
            'slug'        => 'judul',
            'content'     => 'Isi.',
        ]);

        $this->actingAs($this->admin)
            ->delete("/admin/categories/{$cat->id}")
            ->assertSessionHasErrors('category');

        $this->assertDatabaseHas('categories', ['id' => $cat->id]);
    }

    public function test_can_delete_unused_category(): void
    {
        $cat = $this->category('Kosong');

        $this->actingAs($this->admin)->delete("/admin/categories/{$cat->id}");

        $this->assertDatabaseMissing('categories', ['id' => $cat->id]);
    }

    public function test_regular_user_cannot_create_category(): void
    {
        $this->actingAs($this->student)->post('/admin/categories', ['name' => 'Liar']);

        $this->assertDatabaseMissing('categories', ['name' => 'Liar']);
    }
}