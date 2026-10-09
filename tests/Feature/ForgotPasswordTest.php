<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_forgot_password_page(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/ForgotPassword'));
    }

    public function test_logged_in_user_can_open_forgot_password_page(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/forgot-password')->assertOk();
    }
}