<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 書籍 CRUD（詳細表示・登録・編集・削除）
 * 所有者のみ操作可・他ユーザーは 403 の確認は BookAuthorizationTest と合わせて行う
 */
class BookCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $other;

    private Genre $novel;

    private Genre $tech;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->other = User::factory()->create();
        $this->novel = Genre::create(['name' => '小説']);
        $this->tech = Genre::create(['name' => '技術書']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_date' => '1905-01-01',
            'description' => '猫の視点から描いた作品。',
            'image_url' => 'https://example.com/neko.jpg',
            'genres' => [$this->novel->id],
        ], $overrides);
    }

    private function ownersBook(): Book
    {
        $book = Book::factory()->create(['user_id' => $this->owner->id, 'title' => '吾輩は猫である']);
        $book->genres()->sync([$this->novel->id]);

        return $book;
    }

    // ---- 詳細 ----

    public function test_show_displays_book_genres_and_reviews(): void
    {
        $book = $this->ownersBook();
        Review::factory()->create(['book_id' => $book->id, 'rating' => 5, 'comment' => '最高の一冊でした']);

        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertViewIs('books.show')
            ->assertViewHas('book', fn (Book $viewBook) => $viewBook->is($book))
            ->assertSee('吾輩は猫である')
            ->assertSee('小説')
            ->assertSee('最高の一冊でした');
    }

    public function test_show_returns_404_for_missing_book(): void
    {
        $this->get(route('books.show', 9999))->assertNotFound();
    }

    // ---- 登録 ----

    public function test_store_creates_book_with_owner_and_genres(): void
    {
        $response = $this->actingAs($this->owner)
            ->post(route('books.store'), $this->payload(['genres' => [$this->novel->id, $this->tech->id]]))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', '書籍を登録しました');

        $book = Book::where('isbn', '9784101010014')->firstOrFail();
        // 登録後は登録した書籍の詳細ページへ
        $response->assertRedirect(route('books.show', $book));
        $this->assertSame($this->owner->id, $book->user_id);
        $this->assertSame('1905-01-01', $book->published_at->format('Y-m-d'));
        $this->assertEqualsCanonicalizing([$this->novel->id, $this->tech->id], $book->genres->pluck('id')->all());
    }

    public function test_store_with_invalid_data_does_not_create_book(): void
    {
        $this->actingAs($this->owner)
            ->from(route('books.create'))
            ->post(route('books.store'), $this->payload(['title' => '', 'genres' => []]))
            ->assertRedirect(route('books.create'))
            ->assertSessionHasErrors(['title', 'genres']);

        $this->assertDatabaseCount('books', 0);
    }

    // ---- 編集 ----

    public function test_owner_can_update_book_and_sync_genres(): void
    {
        $book = $this->ownersBook();

        $this->actingAs($this->owner)
            ->put(route('books.update', $book), $this->payload(['title' => '坊っちゃん', 'genres' => [$this->tech->id]]))
            ->assertRedirect(route('books.index'))
            ->assertSessionHasNoErrors();

        $book->refresh();
        $this->assertSame('坊っちゃん', $book->title);
        $this->assertSame([$this->tech->id], $book->genres->pluck('id')->all());
    }

    public function test_other_user_gets_403_on_edit_update_and_delete(): void
    {
        $book = $this->ownersBook();

        $this->actingAs($this->other)->get(route('books.edit', $book))->assertForbidden();
        $this->actingAs($this->other)->put(route('books.update', $book), $this->payload(['title' => '改ざん']))->assertForbidden();
        $this->actingAs($this->other)->delete(route('books.destroy', $book))->assertForbidden();

        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => '吾輩は猫である']);
        $this->assertDatabaseHas('book_genre', ['book_id' => $book->id, 'genre_id' => $this->novel->id]);
    }

    public function test_edit_returns_404_for_missing_book(): void
    {
        $this->actingAs($this->owner)->get(route('books.edit', 9999))->assertNotFound();
    }

    // ---- 削除 ----

    public function test_owner_can_delete_book_and_its_relations(): void
    {
        $book = $this->ownersBook();
        Review::factory()->create(['book_id' => $book->id]);

        $this->actingAs($this->owner)
            ->delete(route('books.destroy', $book))
            ->assertRedirect(route('books.index'))
            ->assertSessionHas('success', '書籍を削除しました');

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
        $this->assertDatabaseMissing('book_genre', ['book_id' => $book->id]);
        $this->assertDatabaseMissing('reviews', ['book_id' => $book->id]);
    }
}
