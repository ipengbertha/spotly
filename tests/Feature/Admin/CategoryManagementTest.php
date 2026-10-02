<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
        return Category::create(['name' => $name, 'slug' => Str::slug($name)]);
    }

    private function postIn(Category $cat, string $slug): Post
    {
        return Post::create([
            'user_id'     => $this->student->id,
            'category_id' => $cat->id,
            'title'       => "Judul {$slug}",
            'slug'        => $slug,
            'content'     => 'Isi.',
        ]);
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

    public function test_can_delete_unused_category(): void
    {
        $cat = $this->category('Kosong');

        $this->actingAs($this->admin)->delete("/admin/categories/{$cat->id}");

        $this->assertDatabaseMissing('categories', ['id' => $cat->id]);
        $this->assertDatabaseMissing('categories', ['slug' => Category::FALLBACK_SLUG]);
    }

    public function test_deleting_category_moves_posts_to_fallback(): void
    {
        $cat = $this->category('Info Lomba');
        $a = $this->postIn($cat, 'a');
        $b = $this->postIn($cat, 'b');

        $this->actingAs($this->admin)
            ->delete("/admin/categories/{$cat->id}")
            ->assertSessionHasNoErrors();

        $fallback = Category::where('slug', Category::FALLBACK_SLUG)->first();

        $this->assertNotNull($fallback);
        $this->assertDatabaseMissing('categories', ['id' => $cat->id]);
        $this->assertSame($fallback->id, $a->fresh()->category_id);
        $this->assertSame($fallback->id, $b->fresh()->category_id);
    }

    public function test_deleting_category_moves_posts_to_chosen_category(): void
    {
        $cat    = $this->category('Info Lomba');
        $target = $this->category('Prestasi');
        $post   = $this->postIn($cat, 'a');

        $this->actingAs($this->admin)
            ->delete("/admin/categories/{$cat->id}", ['move_to' => $target->id])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('categories', ['id' => $cat->id]);
        $this->assertSame($target->id, $post->fresh()->category_id);
        $this->assertDatabaseMissing('categories', ['slug' => Category::FALLBACK_SLUG]);
    }

    public function test_cannot_move_posts_to_the_same_category(): void
    {
        $cat  = $this->category('Info Lomba');
        $post = $this->postIn($cat, 'a');

        $this->actingAs($this->admin)
            ->delete("/admin/categories/{$cat->id}", ['move_to' => $cat->id])
            ->assertSessionHasErrors('move_to');

        $this->assertDatabaseHas('categories', ['id' => $cat->id]);
        $this->assertSame($cat->id, $post->fresh()->category_id);
    }

    public function test_cannot_move_posts_to_missing_category(): void
    {
        $cat = $this->category('Info Lomba');
        $this->postIn($cat, 'a');

        $this->actingAs($this->admin)
            ->delete("/admin/categories/{$cat->id}", ['move_to' => 9999])
            ->assertSessionHasErrors('move_to');

        $this->assertDatabaseHas('categories', ['id' => $cat->id]);
    }

    public function test_moving_posts_keeps_their_updated_at(): void
    {
        $cat  = $this->category('Info Lomba');
        $post = $this->postIn($cat, 'a');
        $old  = now()->subDays(3)->startOfSecond();
        DB::table('posts')->where('id', $post->id)->update(['updated_at' => $old]);

        $this->actingAs($this->admin)->delete("/admin/categories/{$cat->id}");

        $this->assertTrue($post->fresh()->updated_at->equalTo($old));
    }

    public function test_fallback_category_cannot_be_deleted(): void
    {
        $fallback = Category::fallback();

        $this->actingAs($this->admin)
            ->delete("/admin/categories/{$fallback->id}")
            ->assertSessionHasErrors('category');

        $this->assertDatabaseHas('categories', ['id' => $fallback->id]);
    }

    public function test_fallback_category_cannot_be_renamed(): void
    {
        $fallback = Category::fallback();

        $this->actingAs($this->admin)
            ->put("/admin/categories/{$fallback->id}", ['name' => 'Lain-lain'])
            ->assertSessionHasErrors('name');

        $this->assertSame('Lainnya', $fallback->fresh()->name);
    }

    public function test_regular_user_cannot_create_category(): void
    {
        $this->actingAs($this->student)->post('/admin/categories', ['name' => 'Liar']);

        $this->assertDatabaseMissing('categories', ['name' => 'Liar']);
    }
}