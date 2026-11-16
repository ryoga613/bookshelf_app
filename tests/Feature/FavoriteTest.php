<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * お気に入り（追加・解除・トグル・一覧表示・ゲスト制限）
 */
class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Book $book;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->book = Book::factory()->create(['title' => '吾輩は猫である']);
    }

    private function toggle()
    {
        return $this->actingAs($this->user)
            ->from(route('books.show', $this->book))
            ->post(route('favorites.toggle', $this->book));
    }

    public function test_add_favorite(): void
    {
        $this->toggle()->assertRedirect(route('books.show', $this->book));

        $this->assertDatabaseHas('favorites', ['user_id' => $this->user->id, 'book_id' => $this->book->id]);
    }

    public function test_remove_favorite(): void
    {
        $this->user->favoriteBooks()->attach($this->book->id);

        $this->toggle()->assertRedirect(route('books.show', $this->book));

        $this->assertDatabaseMissing('favorites', ['user_id' => $this->user->id, 'book_id' => $this->book->id]);
    }

    public function test_toggle_switches_on_and_off_without_duplicates(): void
    {
        $this->toggle();
        $this->assertDatabaseCount('favorites', 1);

        $this->toggle();
        $this->assertDatabaseCount('favorites', 0);

        $this->toggle();
        $this->assertDatabaseCount('favorites', 1);
    }

    public function test_toggle_does_not_affect_other_users_favorites(): void
    {
        $other = User::factory()->create();
        $other->favoriteBooks()->attach($this->book->id);

        $this->toggle();
        $this->toggle();

        $this->assertDatabaseHas('favorites', ['user_id' => $other->id, 'book_id' => $this->book->id]);
    }

    public function test_toggle_returns_404_for_missing_book(): void
    {
        $this->actingAs($this->user)->post(route('favorites.toggle', 9999))->assertNotFound();
    }

    public function test_index_shows_only_my_favorites(): void
    {
        $this->user->favoriteBooks()->attach($this->book->id);
        Book::factory()->create(['title' => 'お気に入りでない本']);
        $othersFavorite = Book::factory()->create(['title' => '他人のお気に入り']);
        User::factory()->create()->favoriteBooks()->attach($othersFavorite->id);

        $this->actingAs($this->user)->get(route('favorites.index'))
            ->assertOk()
            ->assertViewIs('favorites.index')
            ->assertViewHas('books', fn ($books) => $books->pluck('id')->all() === [$this->book->id])
            ->assertSee('吾輩は猫である')
            ->assertDontSee('お気に入りでない本')
            ->assertDontSee('他人のお気に入り');
    }

    public function test_index_is_empty_without_favorites(): void
    {
        $this->actingAs($this->user)->get(route('favorites.index'))
            ->assertOk()
            ->assertViewHas('books', fn ($books) => $books->isEmpty());
    }

    public function test_guest_cannot_toggle_or_see_favorites(): void
    {
        $this->post(route('favorites.toggle', $this->book))->assertRedirect(route('login'));
        $this->get(route('favorites.index'))->assertRedirect(route('login'));

        $this->assertDatabaseCount('favorites', 0);
    }
}
