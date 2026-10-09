<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create(['role' => 'user', 'password' => 'OldPass1!']);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/settings')->assertRedirect('/login');
    }

    public function test_user_and_admin_can_open_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($this->student)->get('/settings')
            ->assertInertia(fn ($page) => $page->where('isAdmin', false));
        $this->actingAs($admin)->get('/settings')
            ->assertInertia(fn ($page) => $page->where('isAdmin', true));
    }

    public function test_user_can_update_profile(): void
    {
        $this->actingAs($this->student)->post('/settings/profile', [
            'name'  => 'Nama Baru',
            'email' => 'baru@example.com',
            'bio'   => 'Anggota OSIS.',
        ])->assertSessionHasNoErrors();

        $fresh = $this->student->fresh();
        $this->assertSame('Nama Baru', $fresh->name);
        $this->assertSame('baru@example.com', $fresh->email);
        $this->assertSame('Anggota OSIS.', $fresh->bio);
    }

    public function test_email_of_another_user_is_rejected(): void
    {
        $other = User::factory()->create();

        $this->actingAs($this->student)->post('/settings/profile', [
            'name'  => 'Nama',
            'email' => $other->email,
        ])->assertSessionHasErrors('email');
    }

    public function test_keeping_own_email_is_allowed(): void
    {
        $this->actingAs($this->student)->post('/settings/profile', [
            'name'  => 'Nama',
            'email' => $this->student->email,
        ])->assertSessionHasNoErrors();
    }

    public function test_role_cannot_be_changed_from_settings(): void
    {
        $this->actingAs($this->student)->post('/settings/profile', [
            'name'  => 'Nama',
            'email' => $this->student->email,
            'role'  => 'admin',
        ]);

        $this->assertSame('user', $this->student->fresh()->role);
    }

    public function test_avatar_can_be_uploaded_replaced_and_removed(): void
    {
        Storage::fake('public');

        $this->actingAs($this->student)->post('/settings/profile', [
            'name'   => 'Nama',
            'email'  => $this->student->email,
            'avatar' => UploadedFile::fake()->image('a.jpg'),
        ])->assertSessionHasNoErrors();
        $first = $this->student->fresh()->avatar;
        Storage::disk('public')->assertExists($first);

        $this->actingAs($this->student)->post('/settings/profile', [
            'name'   => 'Nama',
            'email'  => $this->student->email,
            'avatar' => UploadedFile::fake()->image('b.png'),
        ]);
        Storage::disk('public')->assertMissing($first);

        $second = $this->student->fresh()->avatar;
        $this->actingAs($this->student)->post('/settings/profile', [
            'name'          => 'Nama',
            'email'         => $this->student->email,
            'remove_avatar' => true,
        ]);
        Storage::disk('public')->assertMissing($second);
        $this->assertNull($this->student->fresh()->avatar);
    }

    public function test_avatar_rejects_non_image(): void
    {
        Storage::fake('public');

        $this->actingAs($this->student)->post('/settings/profile', [
            'name'   => 'Nama',
            'email'  => $this->student->email,
            'avatar' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('avatar');
    }

    public function test_user_can_change_password(): void
    {
        $this->actingAs($this->student)->put('/settings/password', [
            'current_password'      => 'OldPass1!',
            'password'              => 'NewPass2@',
            'password_confirmation' => 'NewPass2@',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('NewPass2@', $this->student->fresh()->password));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $this->actingAs($this->student)->put('/settings/password', [
            'current_password'      => 'SalahBanget1!',
            'password'              => 'NewPass2@',
            'password_confirmation' => 'NewPass2@',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('OldPass1!', $this->student->fresh()->password));
    }

    public function test_weak_or_unconfirmed_password_is_rejected(): void
    {
        $this->actingAs($this->student)->put('/settings/password', [
            'current_password'      => 'OldPass1!',
            'password'              => 'lemah',
            'password_confirmation' => 'lemah',
        ])->assertSessionHasErrors('password');

        $this->actingAs($this->student)->put('/settings/password', [
            'current_password'      => 'OldPass1!',
            'password'              => 'NewPass2@',
            'password_confirmation' => 'Beda3#xx',
        ])->assertSessionHasErrors('password');
    }
}