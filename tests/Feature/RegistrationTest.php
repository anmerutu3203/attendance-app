<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_and_is_redirected_to_attendance(): void
    {
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/attendance');
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'admin_status' => false,
        ]);
    }

    public function test_name_is_required(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => '',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['name' => 'お名前を入力してください']);
    }

    public function test_email_must_be_a_valid_format(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'not-an-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email' => 'メールアドレスはメール形式で入力してください']);
    }

    public function test_password_must_be_at_least_8_characters(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'newuser@example.com',
            'password' => 'short1',
            'password_confirmation' => 'short1',
        ]);

        $response->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください']);
    }

    public function test_password_confirmation_must_match(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertSessionHasErrors(['password' => 'パスワードと一致しません']);
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->from('/register')->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }
}
