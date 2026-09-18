<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_log_in(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'admin_status' => true,
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin/attendance/list');
        $this->assertAuthenticatedAs(User::where('email', 'admin@example.com')->first(), 'admin');
    }

    public function test_a_general_user_cannot_log_in_through_the_admin_form(): void
    {
        User::factory()->create([
            'email' => 'user1@example.com',
            'password' => Hash::make('password'),
            'admin_status' => false,
        ]);

        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => 'user1@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
        $this->assertGuest('admin');
    }

    public function test_admin_routes_require_admin_authentication(): void
    {
        $response = $this->get('/admin/attendance/list');

        $response->assertRedirect('/login');
    }
}
