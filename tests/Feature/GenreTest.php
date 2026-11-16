<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ジャンル（一覧・登録・詳細・編集・削除制約）
 */
class GenreTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ---- 一覧 ----

    public function test_index_lists_genres_with_book_counts(): void
    {
        $novel = Genre::create(['name' => '小説']);
        Genre::create(['name' => '技術書']);
        Book::factory(2)->create()->each(fn (Book $book) => $book->genres()->sync([$novel->id]));

        $response = $this->actingAs($this->user)->get(route('genres.index'))
            ->assertOk()
            ->assertViewIs('genres.index')
            ->assertSee('小説')
            ->assertSee('技術書');

        $counts = $response->viewData('genres')->pluck('books_count', 'name')->all();
        $this->assertEquals(['小説' => 2, '技術書' => 0], $counts);
    }

    // ---- 登録 ----

    public function test_create_form_is_shown(): void
    {
        $this->actingAs($this->user)->get(route('genres.create'))->assertOk()->assertViewIs('genres.create');
    }

    public function test_store_creates_genre(): void
    {
        $this->actingAs($this->user)
            ->post(route('genres.store'), ['name' => 'ミステリー'])
            ->assertRedirect(route('genres.index'))
            ->assertSessionHas('success', 'ジャンルが作成されました。');

        $this->assertDatabaseHas('genres', ['name' => 'ミステリー']);
    }

    public function test_store_validation(): void
    {
        Genre::create(['name' => '小説']);

        $this->actingAs($this->user)->post(route('genres.store'), ['name' => ''])
            ->assertSessionHasErrors(['name' => 'ジャンル名は必須です。']);
        $this->actingAs($this->user)->post(route('genres.store'), ['name' => '小説'])
            ->assertSessionHasErrors(['name' => 'このジャンル名は既に使用されています。']);
        $this->actingAs($this->user)->post(route('genres.store'), ['name' => str_repeat('あ', 256)])
            ->assertSessionHasErrors(['name' => 'ジャンル名は255文字以内で入力してください。']);

        $this->assertDatabaseCount('genres', 1);
    }

    // ---- 詳細 ----

    public function test_show_lists_books_in_genre_only(): void
    {
        $novel = Genre::create(['name' => '小説']);
        $tech = Genre::create(['name' => '技術書']);
        Book::factory()->create(['title' => '吾輩は猫である'])->genres()->sync([$novel->id]);
        Book::factory()->create(['title' => 'リーダブルコード'])->genres()->sync([$tech->id]);

        $this->actingAs($this->user)->get(route('genres.show', $novel))
            ->assertOk()
            ->assertViewIs('genres.show')
            ->assertViewHas('genre', fn (Genre $genre) => $genre->is($novel))
            ->assertSee('吾輩は猫である')
            ->assertDontSee('リーダブルコード');
    }

    public function test_show_returns_404_for_missing_genre(): void
    {
        $this->actingAs($this->user)->get(route('genres.show', 9999))->assertNotFound();
    }

    // ---- 編集 ----

    public function test_edit_form_shows_genre(): void
    {
        $genre = Genre::create(['name' => '小説']);

        $this->actingAs($this->user)->get(route('genres.edit', $genre))
            ->assertOk()
            ->assertViewHas('genre', fn (Genre $viewGenre) => $viewGenre->is($genre))
            ->assertSee('小説');
    }

    public function test_update_renames_genre(): void
    {
        $genre = Genre::create(['name' => '小説']);

        $this->actingAs($this->user)
            ->put(route('genres.update', $genre), ['name' => '文学'])
            ->assertRedirect(route('genres.index'))
            ->assertSessionHas('success', 'ジャンルが更新されました。');

        $this->assertDatabaseHas('genres', ['id' => $genre->id, 'name' => '文学']);
    }

    public function test_update_allows_same_name_but_rejects_other_genres_name(): void
    {
        $genre = Genre::create(['name' => '小説']);
        Genre::create(['name' => '技術書']);

        $this->actingAs($this->user)->put(route('genres.update', $genre), ['name' => '小説'])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->user)->put(route('genres.update', $genre), ['name' => '技術書'])
            ->assertSessionHasErrors(['name' => 'このジャンル名は既に使用されています。']);

        $this->assertDatabaseHas('genres', ['id' => $genre->id, 'name' => '小説']);
    }

    // ---- 削除制約 ----

    public function test_genre_without_books_can_be_deleted(): void
    {
        $genre = Genre::create(['name' => '小説']);

        $this->actingAs($this->user)
            ->delete(route('genres.destroy', $genre))
            ->assertRedirect(route('genres.index'))
            ->assertSessionHas('success', 'ジャンルが削除されました。');

        $this->assertDatabaseMissing('genres', ['id' => $genre->id]);
    }

    public function test_genre_with_books_cannot_be_deleted(): void
    {
        $genre = Genre::create(['name' => '小説']);
        $book = Book::factory()->create();
        $book->genres()->sync([$genre->id]);

        $this->actingAs($this->user)
            ->delete(route('genres.destroy', $genre))
            ->assertRedirect(route('genres.index'))
            ->assertSessionHas('error', 'このジャンルには書籍が紐付いているため削除できません。');

        $this->assertDatabaseHas('genres', ['id' => $genre->id]);
        $this->assertDatabaseHas('book_genre', ['book_id' => $book->id, 'genre_id' => $genre->id]);

        // エラーメッセージが一覧画面に表示される
        $this->actingAs($this->user)->get(route('genres.index'))
            ->assertSee('このジャンルには書籍が紐付いているため削除できません。');
    }

    // ---- ゲスト ----

    public function test_guest_is_redirected_to_login(): void
    {
        $genre = Genre::create(['name' => '小説']);

        $this->get(route('genres.index'))->assertRedirect(route('login'));
        $this->post(route('genres.store'), ['name' => 'ミステリー'])->assertRedirect(route('login'));
        $this->delete(route('genres.destroy', $genre))->assertRedirect(route('login'));

        $this->assertDatabaseCount('genres', 1);
    }
}
