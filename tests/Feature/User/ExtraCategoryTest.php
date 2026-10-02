<?php

namespace Tests\Feature\User;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtraCategoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $student;
    private Category $a;
    private Category $b;
    private Category $c;
    private Category $d;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin   = User::factory()->create(['role' => 'admin']);
        $this->student = User::factory()->create(['role' => 'user']);

        $this->a = Category::create(['name' => 'Prestasi', 'slug' => 'prestasi']);
        $this->b = Category::create(['name' => 'Info Lomba', 'slug' => 'info-lomba']);
        $this->c = Category::create(['name' => 'Opini', 'slug' => 'opini']);
        $this->d = Category::create(['name' => 'Karya Siswa', 'slug' => 'karya-siswa']);
    }

    private function payload(array $extra = [], ?int $primary = null): array
    {
        return [
            'title'              => 'Judul Tes',
            'content'            => 'Isi tes.',
            'category_id'        => $primary ?? $this->a->id,
            'extra_category_ids' => $extra,
        ];
    }

    private function postWith(array $extraIds, bool $published = false): Post
    {
        $post = Post::create([
            'user_id'     => $this->student->id,
            'category_id' => $this->a->id,
            'title'       => 'Judul',
            'slug'        => 'judul-' . uniqid(),
            'content'     => 'Isi.',
        ]);
        $post->extraCategories()->sync($extraIds);

        if ($published) {
            $post->markSubmitted();
            $post->markPublished(now()->addDays(5));
        }

        return $post;
    }

    public function test_student_can_save_post_with_extra_categories(): void
    {
        $this->actingAs($this->student)
            ->post('/user/posts', $this->payload([$this->b->id, $this->c->id]))
            ->assertSessionHasNoErrors();

        $post = Post::firstOrFail();

        $this->assertSame($this->a->id, $post->category_id);
        $this->assertEqualsCanonicalizing(
            [$this->b->id, $this->c->id],
            $post->extraCategories->pluck('id')->all()
        );
    }

    public function test_more_than_two_extra_categories_is_rejected(): void
    {
        $this->actingAs($this->student)
            ->post('/user/posts', $this->payload([$this->b->id, $this->c->id, $this->d->id]))
            ->assertSessionHasErrors('extra_category_ids');

        $this->assertSame(0, Post::count());
    }

    public function test_extra_category_cannot_equal_primary(): void
    {
        $this->actingAs($this->student)
            ->post('/user/posts', $this->payload([$this->a->id]))
            ->assertSessionHasErrors('extra_category_ids');

        $this->assertSame(0, Post::count());
    }

    public function test_duplicate_extra_categories_are_rejected(): void
    {
        $this->actingAs($this->student)
            ->post('/user/posts', $this->payload([$this->b->id, $this->b->id]))
            ->assertSessionHasErrors('extra_category_ids.1');

        $this->assertSame(0, Post::count());
    }

    public function test_update_replaces_extra_categories(): void
    {
        $post = $this->postWith([$this->b->id]);

        $this->actingAs($this->student)
            ->put("/user/posts/{$post->id}", $this->payload([$this->c->id, $this->d->id]))
            ->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing(
            [$this->c->id, $this->d->id],
            $post->fresh()->extraCategories->pluck('id')->all()
        );
    }

    public function test_feed_filter_matches_extra_category(): void
    {
        $this->postWith([$this->b->id], published: true);

        $this->actingAs($this->student)
            ->get("/user/beranda?category={$this->b->id}")
            ->assertInertia(fn ($page) => $page->has('posts.data', 1));

        $this->actingAs($this->student)
            ->get("/user/beranda?category={$this->d->id}")
            ->assertInertia(fn ($page) => $page->has('posts.data', 0));
    }

    public function test_deleting_primary_category_promotes_first_extra(): void
    {
        $post = $this->postWith([$this->b->id, $this->c->id]);

        $this->actingAs($this->admin)
            ->delete("/admin/categories/{$this->a->id}")
            ->assertSessionHasNoErrors();

        $fresh = $post->fresh();

        $this->assertSame($this->b->id, $fresh->category_id);
        $this->assertSame([$this->c->id], $fresh->extraCategories->pluck('id')->all());
        $this->assertDatabaseMissing('categories', ['slug' => Category::FALLBACK_SLUG]);
    }

    public function test_deleting_category_used_only_as_extra_keeps_primary(): void
    {
        $post = $this->postWith([$this->b->id]);

        $this->actingAs($this->admin)
            ->delete("/admin/categories/{$this->b->id}")
            ->assertSessionHasNoErrors();

        $fresh = $post->fresh();

        $this->assertSame($this->a->id, $fresh->category_id);
        $this->assertSame(0, $fresh->extraCategories->count());
        $this->assertDatabaseMissing('categories', ['id' => $this->b->id]);
    }
}