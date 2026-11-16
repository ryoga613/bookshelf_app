<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BookPolicy による書籍の編集・削除の認可（所有者のみ可）
 */
class BookAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $other;

    private Book $book;

    private Genre $genre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->other = User::factory()->create();
        $this->genre = Genre::create(['name' => '小説']);
        $this->book = Book::factory()->create([
            'user_id' => $this->owner->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
        ]);
        $this->book->genres()->sync([$this->genre->id]);
    }

    private function updatePayload(array $overrides = []): array
    {
        return array_merge([
            'title' => '坊っちゃん',
            'author' => '夏目漱石',
            'isbn' => '9784101010021',
            'published_date' => '1906-04-01',
            'description' => '',
            'image_url' => '',
            'genres' => [$this->genre->id],
        ], $overrides);
    }

    // ---- 所有者 ----

    public function test_owner_can_see_edit_page_with_correct_data(): void
    {
        $response = $this->actingAs($this->owner)->get(route('books.edit', $this->book));

        $response->assertOk()
            ->assertViewIs('books.edit')
            ->assertViewHas('book', fn (Book $book) => $book->is($this->book))
            ->assertViewHas('genres', fn ($genres) => $genres->pluck('id')->all() === [$this->genre->id])
            ->assertSee('吾輩は猫である')
            ->assertSee('9784101010014');
    }

    public function test_owner_can_update_book(): void
    {
        $this->actingAs($this->owner)
            ->put(route('books.update', $this->book), $this->updatePayload())
            ->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('books', ['id' => $this->book->id, 'title' => '坊っちゃん']);
    }

    public function test_owner_can_delete_book(): void
    {
        $this->actingAs($this->owner)
            ->delete(route('books.destroy', $this->book))
            ->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', ['id' => $this->book->id]);
    }

    public function test_owner_sees_edit_and_delete_buttons_on_detail(): void
    {
        $this->actingAs($this->owner)
            ->get(route('books.show', $this->book))
            ->assertOk()
            ->assertViewHas('book', fn (Book $book) => $book->is($this->book))
            ->assertSee(route('books.edit', $this->book))
            // 削除フォーム（URL は詳細ページと同じなので、確認ダイアログの文言で判定する）
            ->assertSee('本当に削除しますか？');
    }

    // ---- 他のユーザー ----

    public function test_other_user_cannot_see_edit_page(): void
    {
        $this->actingAs($this->other)
            ->get(route('books.edit', $this->book))
            ->assertForbidden();
    }

    public function test_other_user_cannot_update_book(): void
    {
        $this->actingAs($this->other)
            ->put(route('books.update', $this->book), $this->updatePayload())
            ->assertForbidden();

        $this->assertDatabaseHas('books', ['id' => $this->book->id, 'title' => '吾輩は猫である']);
    }

    public function test_other_user_cannot_delete_book(): void
    {
        $this->actingAs($this->other)
            ->delete(route('books.destroy', $this->book))
            ->assertForbidden();

        $this->assertDatabaseHas('books', ['id' => $this->book->id]);
    }

    public function test_other_user_does_not_see_edit_and_delete_buttons(): void
    {
        $this->actingAs($this->other)
            ->get(route('books.show', $this->book))
            ->assertOk()
            ->assertSee('吾輩は猫である')
            ->assertDontSee(route('books.edit', $this->book))
            ->assertDontSee('本当に削除しますか？');
    }

    // ---- ゲスト ----

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('books.edit', $this->book))->assertRedirect(route('login'));
        $this->put(route('books.update', $this->book), $this->updatePayload())->assertRedirect(route('login'));
        $this->delete(route('books.destroy', $this->book))->assertRedirect(route('login'));

        $this->assertDatabaseHas('books', ['id' => $this->book->id, 'title' => '吾輩は猫である']);
    }
}
