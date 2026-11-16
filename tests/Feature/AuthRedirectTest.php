<?php

namespace Tests\Feature;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * 認証済みユーザーのリダイレクト（RedirectIfAuthenticated / Fortify の home）
 */
class AuthRedirectTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['password' => Hash::make('password')]);
    }

    public function test_home_is_book_index(): void
    {
        $this->assertSame('/books', RouteServiceProvider::HOME);
    }

    public function test_authenticated_user_is_redirected_from_login_page(): void
    {
        $this->actingAs($this->user)->get('/login')->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_authenticated_user_is_redirected_from_register_page(): void
    {
        $this->actingAs($this->user)->get('/register')->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_authenticated_user_cannot_post_login_again(): void
    {
        $this->actingAs($this->user)
            ->post('/login', ['email' => $this->user->email, 'password' => 'password'])
            ->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_user_is_redirected_to_home_after_login(): void
    {
        $this->post('/login', ['email' => $this->user->email, 'password' => 'password'])
            ->assertRedirect(RouteServiceProvider::HOME);

        $this->assertAuthenticatedAs($this->user);
    }

    public function test_user_is_redirected_to_home_after_register(): void
    {
        $this->post('/register', [
            'name' => '新規ユーザー',
            'email' => 'new@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(RouteServiceProvider::HOME);

        $this->assertAuthenticated();
    }

    public function test_guest_can_see_login_and_register_pages(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    public function test_guest_is_redirected_to_intended_page_after_login(): void
    {
        $this->get(route('reports.index'))->assertRedirect(route('login'));

        $this->post('/login', ['email' => $this->user->email, 'password' => 'password'])
            ->assertRedirect(route('reports.index'));
    }
}
