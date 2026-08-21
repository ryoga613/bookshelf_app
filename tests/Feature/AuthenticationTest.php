<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertViewIs('auth.login');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response = $this->ActingAs($user)->get('/books/create');
        $response->assertViewIs('books.create');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'aaaaa',
        ]);

        $response->assertRedirect('/login');
    }

    /**
     * 存在しないメールアドレスではログインできないこと
     */
    public function test_users_can_not_authenticate_with_unregistered_email(): void
    {
        $user = User::factory()->create([
            'name' => '山田太郎',
            'email' => 'test@email.com',
            'password' => Hash::make('password'),

        ]);

        $response = $this->get('/books', [
            'email' => 'aaaaa@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_email_and_password_are_required(): void
    {
        $response = $this->from('/login')->post('/login', []);

        $response->assertRedirect('/login');
    }

    public function test_users_are_rate_limited_after_too_many_login_attempts(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_guests_are_redirected_from_authenticated_routes(): void
    {
        $response = $this->get('/books');

        $response->assertRedirect('/login');
    }
}
