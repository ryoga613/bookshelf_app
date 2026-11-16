<?php

namespace Tests\Feature\api;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * 書き込み系 API の Sanctum 認証・認可（未認証 401 / 認証時 2xx / 他ユーザー 403）
 */
class SanctumAuthTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $other;

    private Book $book;

    private Genre $genre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create(['password' => Hash::make('password')]);
        $this->other = User::factory()->create();
        $this->genre = Genre::create(['name' => '小説']);
        $this->book = Book::factory()->create(['user_id' => $this->owner->id, 'title' => '吾輩は猫である']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => '坊っちゃん',
            'author' => '夏目漱石',
            'isbn' => '9784101010021',
            'published_at' => '1906-04-01',
            'description' => '痛快な青春小説。',
            'image_url' => 'https://example.com/botchan.jpg',
            'genre_ids' => [$this->genre->id],
        ], $overrides);
    }

    // ---- 未認証: 401 ----

    public function test_store_requires_authentication(): void
    {
        $this->postJson('/api/v1/books', $this->payload())
            ->assertUnauthorized()
            ->assertJson(['message' => 'Unauthenticated.']);

        $this->assertDatabaseMissing('books', ['title' => '坊っちゃん']);
    }

    public function test_update_requires_authentication(): void
    {
        $this->putJson("/api/v1/books/{$this->book->id}", $this->payload())->assertUnauthorized();

        $this->assertDatabaseHas('books', ['id' => $this->book->id, 'title' => '吾輩は猫である']);
    }

    public function test_delete_requires_authentication(): void
    {
        $this->deleteJson("/api/v1/books/{$this->book->id}")->assertUnauthorized();

        $this->assertDatabaseHas('books', ['id' => $this->book->id]);
    }

    public function test_invalid_token_is_rejected(): void
    {
        $this->withToken('invalid-token')
            ->postJson('/api/v1/books', $this->payload())
            ->assertUnauthorized();
    }

    public function test_read_endpoints_are_public(): void
    {
        $this->getJson('/api/v1/books')->assertOk();
        $this->getJson("/api/v1/books/{$this->book->id}")->assertOk();
    }

    // ---- 認証時: 2xx ----

    public function test_token_from_login_can_store_book(): void
    {
        $token = $this->postJson('/api/login', [
            'email' => $this->owner->email,
            'password' => 'password',
        ])->assertOk()->json('access_token');

        $this->withToken($token)
            ->postJson('/api/v1/books', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.user_id', $this->owner->id);
    }

    public function test_owner_can_update_book(): void
    {
        $this->actingAs($this->owner, 'sanctum')
            ->putJson("/api/v1/books/{$this->book->id}", $this->payload())
            ->assertOk()
            ->assertJsonPath('data.title', '坊っちゃん');
    }

    public function test_owner_can_delete_book(): void
    {
        $this->actingAs($this->owner, 'sanctum')
            ->deleteJson("/api/v1/books/{$this->book->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('books', ['id' => $this->book->id]);
    }

    // ---- 他ユーザー: 403 ----

    public function test_other_user_cannot_update_book(): void
    {
        $this->actingAs($this->other, 'sanctum')
            ->putJson("/api/v1/books/{$this->book->id}", $this->payload())
            ->assertForbidden();

        $this->assertDatabaseHas('books', ['id' => $this->book->id, 'title' => '吾輩は猫である']);
    }

    public function test_other_user_cannot_delete_book(): void
    {
        $this->actingAs($this->other, 'sanctum')
            ->deleteJson("/api/v1/books/{$this->book->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('books', ['id' => $this->book->id]);
    }

    // ---- ログイン / ログアウト ----

    public function test_login_with_wrong_password_returns_401(): void
    {
        $this->postJson('/api/login', ['email' => $this->owner->email, 'password' => 'wrong'])
            ->assertUnauthorized()
            ->assertJson(['message' => 'ログイン情報が正しくありません。']);
    }

    public function test_logout_requires_authentication(): void
    {
        $this->postJson('/api/logout')->assertUnauthorized();
    }
}
