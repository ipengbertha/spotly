<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
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

    public function test_admin_can_deactivate_user(): void
    {
        $this->actingAs($this->admin)
            ->patch("/admin/users/{$this->student->id}/deactivate")
            ->assertSessionHasNoErrors();

        $this->assertFalse($this->student->fresh()->is_active);
    }

    public function test_admin_can_reactivate_user(): void
    {
        $this->student->setActive(false);

        $this->actingAs($this->admin)->patch("/admin/users/{$this->student->id}/activate");

        $this->assertTrue($this->student->fresh()->is_active);
    }

    public function test_deactivated_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'role'      => 'user',
            'password'  => 'rahasia-123',
            'is_active' => false,
        ]);

        $this->post('/login', ['email' => $user->email, 'password' => 'rahasia-123'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_deactivated_user_session_is_ended(): void
    {
        $this->student->setActive(false);

        $this->actingAs($this->student)
            ->get('/user/dashboard')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_admin_account_cannot_be_deactivated(): void
    {
        $other = User::factory()->create(['role' => 'admin']);

        $this->actingAs($this->admin)
            ->patch("/admin/users/{$other->id}/deactivate")
            ->assertSessionHasErrors('user');

        $this->assertTrue($other->fresh()->is_active);
    }

    public function test_regular_user_cannot_deactivate(): void
    {
        $other = User::factory()->create(['role' => 'user']);

        $this->actingAs($this->student)->patch("/admin/users/{$other->id}/deactivate");

        $this->assertTrue($other->fresh()->is_active);
    }
}