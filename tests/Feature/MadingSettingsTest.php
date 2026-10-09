<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MadingSettingsTest extends TestCase
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

    private function valid(array $override = []): array
    {
        return $override + [
            'mading_name'        => 'Mading SMK',
            'mading_description' => 'Pusat informasi sekolah',
            'posts_per_page'     => 6,
        ];
    }

    private function publish(string $slug): void
    {
        $category = Category::firstOrCreate(['slug' => 'umum'], ['name' => 'Umum']);
        $post = Post::create([
            'user_id'     => $this->student->id,
            'category_id' => $category->id,
            'title'       => "Judul {$slug}",
            'slug'        => $slug,
            'content'     => 'Isi tes.',
        ]);
        $post->markSubmitted();
        $post->markPublished(now()->addDays(5));
    }

    public function test_defaults_are_used_when_nothing_saved(): void
    {
        $this->get('/')->assertInertia(fn ($page) => $page
            ->where('mading.name', 'Spotly')
            ->where('mading.description', 'Mading Digital Sekolah'));
    }

    public function test_admin_can_save_mading_settings(): void
    {
        $this->actingAs($this->admin)->put('/settings/mading', $this->valid())
            ->assertSessionHasNoErrors();

        $this->assertSame('Mading SMK', Setting::get('mading_name'));
        $this->assertSame(6, Setting::perPage());
        $this->get('/')->assertInertia(fn ($page) => $page->where('mading.name', 'Mading SMK'));
    }

    public function test_student_cannot_save_mading_settings(): void
    {
        $this->actingAs($this->student)->put('/settings/mading', $this->valid())->assertForbidden();

        $this->assertSame('Spotly', Setting::get('mading_name'));
    }

    public function test_invalid_values_are_rejected(): void
    {
        $this->actingAs($this->admin)->put('/settings/mading', $this->valid(['posts_per_page' => 7]))
            ->assertSessionHasErrors('posts_per_page');
        $this->actingAs($this->admin)->put('/settings/mading', $this->valid(['mading_name' => '']))
            ->assertSessionHasErrors('mading_name');
    }

    public function test_feed_uses_posts_per_page_setting(): void
    {
        foreach (range(1, 7) as $i) {
            $this->publish("post-{$i}");
        }
        $this->actingAs($this->admin)->put('/settings/mading', $this->valid());

        $this->actingAs($this->student)->get('/user/beranda')
            ->assertInertia(fn ($page) => $page->has('posts.data', 6));
    }

    public function test_settings_page_shows_mading_only_to_admin(): void
    {
        $this->actingAs($this->admin)->get('/settings')
            ->assertInertia(fn ($page) => $page->where('mading.name', 'Spotly'));
        $this->actingAs($this->student)->get('/settings')
            ->assertInertia(fn ($page) => $page->where('mading', null));
    }
}