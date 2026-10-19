<?php

namespace Tests\Feature\User;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Support\PostHtml;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PostContentTest extends TestCase
{
    use RefreshDatabase;

    private function student(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    private function category(): Category
    {
        return Category::firstOrCreate(['slug' => 'umum'], ['name' => 'Umum']);
    }

    private function store(User $user, string $content, array $extra = [])
    {
        return $this->actingAs($user)->post('/user/posts', array_merge([
            'title'       => 'Judul',
            'content'     => $content,
            'category_id' => $this->category()->id,
        ], $extra));
    }

    public function test_dangerous_html_is_stripped_before_saving(): void
    {
        $this->store($this->student(), '<p onclick="x()">Halo <strong>dunia</strong></p>'
            . '<script>alert(1)</script><a href="javascript:alert(1)">klik</a>')
            ->assertSessionHasNoErrors();

        $saved = Post::first()->content;

        $this->assertStringContainsString('<strong>dunia</strong>', $saved);
        $this->assertStringNotContainsString('<script', $saved);
        $this->assertStringNotContainsString('onclick', $saved);
        $this->assertStringNotContainsString('javascript:', $saved);
    }

    public function test_links_open_in_new_tab_and_images_are_not_allowed(): void
    {
        $this->store($this->student(), '<p><a href="https://example.com">situs</a><img src="https://x.test/a.png"></p>');

        $saved = Post::first()->content;

        $this->assertStringContainsString('href="https://example.com"', $saved);
        $this->assertStringContainsString('target="_blank"', $saved);
        $this->assertStringContainsString('noopener', $saved);
        $this->assertStringNotContainsString('<img', $saved);
    }

    public function test_tables_and_headings_survive(): void
    {
        $html = '<h2>Jadwal</h2><table><tbody><tr><th colspan="1" rowspan="1"><p>Hari</p></th></tr>'
            . '<tr><td colspan="1" rowspan="1"><p>Senin</p></td></tr></tbody></table>';

        $this->store($this->student(), $html);

        $saved = Post::first()->content;
        $this->assertStringContainsString('<h2>Jadwal</h2>', $saved);
        $this->assertStringContainsString('<table>', $saved);
        $this->assertStringContainsString('Senin', $saved);
    }

    public function test_empty_editor_content_is_rejected(): void
    {
        $this->store($this->student(), '<p></p>')->assertSessionHasErrors('content');
        $this->assertSame(0, Post::count());
    }

    public function test_length_limit_counts_text_not_tags(): void
    {
        $user = $this->student();

        // 5000 huruf di dalam banyak tag: lolos (tag tidak dihitung)
        $ok = '<p><strong>' . str_repeat('a', 5000) . '</strong></p>';
        $this->store($user, $ok)->assertSessionHasNoErrors();

        $tooLong = '<p>' . str_repeat('a', 5001) . '</p>';
        $this->store($user, $tooLong)->assertSessionHasErrors('content');
    }

    public function test_post_page_exposes_html_and_legacy_text_is_escaped_into_paragraphs(): void
    {
        $author = $this->student();
        $make = fn (string $slug, string $content) => Post::forceCreate([
            'user_id' => $author->id, 'category_id' => $this->category()->id,
            'title' => $slug, 'slug' => $slug, 'content' => $content,
            'status' => Post::PUBLISHED, 'published_at' => now()->subDay(), 'expired_at' => now()->addDays(3),
        ]);

        $new    = $make('baru', '<p>Isi <em>baru</em></p>');
        $legacy = $make('lama', "Paragraf satu.\n\nBaris <b>dua</b> & lainnya");

        $this->get("/posts/{$new->slug}")->assertInertia(fn (Assert $page) => $page
            ->where('post.content_html', '<p>Isi <em>baru</em></p>'));

        $this->get("/posts/{$legacy->slug}")->assertInertia(fn (Assert $page) => $page
            ->where('post.content_html', '<p>Paragraf satu.</p><p>Baris &lt;b&gt;dua&lt;/b&gt; &amp; lainnya</p>'));
    }

    public function test_edit_page_gives_legacy_text_as_paragraph_html(): void
    {
        $user = $this->student();
        $post = Post::forceCreate([
            'user_id' => $user->id, 'category_id' => $this->category()->id,
            'title' => 'Lama', 'slug' => 'lama-1', 'content' => "Satu\n\nDua", 'status' => Post::DRAFT,
        ]);

        $this->actingAs($user)->get("/user/posts/{$post->id}/edit")
            ->assertInertia(fn (Assert $page) => $page->where('post.content', '<p>Satu</p><p>Dua</p>'));
    }

    public function test_excerpt_and_plain_text_keep_words_apart(): void
    {
        $this->assertSame('Satu Dua & tiga', PostHtml::plainText('<p>Satu</p><p>Dua &amp; tiga</p>'));

        $post = new Post(['content' => '<p>Satu</p><p>Dua</p>']);
        $this->assertSame('Satu Dua', $post->contentText());
    }
}
