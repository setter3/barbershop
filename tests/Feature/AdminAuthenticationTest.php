<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admin_can_authenticate_and_open_dashboard(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'secret-password',
        ]);

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'secret-password',
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($admin);
        $this->get('/admin')->assertOk()->assertSee('نبض امروز آرشام');
    }

    public function test_regular_user_cannot_authenticate_as_admin(): void
    {
        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => 'secret-password',
        ]);

        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
