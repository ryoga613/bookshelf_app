<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * 書籍一覧（/books）の検索・ジャンルフィルタ・ソート
 */
class BookSearchSortTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function createBook(array $attributes = [], array $genreIds = []): Book
    {
        $book = Book::factory()->create(array_merge(['user_id' => $this->user->id], $attributes));
        $book->genres()->sync($genreIds);

        return $book;
    }

    /**
     * 画面に渡された書籍のタイトルを、表示順のまま配列で取り出す
     */
    private function titlesOf($response): array
    {
        return $response->viewData('books')->pluck('title')->all();
    }

    // ---- 検索・フィルタ ----

    public function test_keyword_matches_title(): void
    {
        $this->createBook(['title' => '吾輩は猫である', 'author' => '夏目漱石']);
        $this->createBook(['title' => 'リーダブルコード', 'author' => 'Dustin Boswell']);

        $response = $this->actingAs($this->user)->get('/books?keyword=猫');

        $response->assertOk()
            ->assertSee('吾輩は猫である')
            ->assertDontSee('リーダブルコード');
        $this->assertSame(['吾輩は猫である'], $this->titlesOf($response));
    }

    public function test_keyword_matches_author(): void
    {
        $this->createBook(['title' => '吾輩は猫である', 'author' => '夏目漱石']);
        $this->createBook(['title' => 'こころ', 'author' => '夏目漱石']);
        $this->createBook(['title' => 'リーダブルコード', 'author' => 'Dustin Boswell']);

        $response = $this->actingAs($this->user)->get('/books?keyword=漱石');

        $response->assertOk()->assertDontSee('リーダブルコード');
        $this->assertEqualsCanonicalizing(['吾輩は猫である', 'こころ'], $this->titlesOf($response));
    }

    public function test_keyword_with_no_match_returns_empty_list(): void
    {
        $this->createBook(['title' => '吾輩は猫である']);

        $response = $this->actingAs($this->user)->get('/books?keyword=存在しない書籍');

        $response->assertOk()->assertDontSee('吾輩は猫である');
        $this->assertSame([], $this->titlesOf($response));
    }

    public function test_genre_filter_shows_only_books_in_genre(): void
    {
        $novel = Genre::create(['name' => '小説']);
        $tech = Genre::create(['name' => '技術書']);
        $this->createBook(['title' => '吾輩は猫である'], [$novel->id]);
        $this->createBook(['title' => 'リーダブルコード'], [$tech->id]);
        $this->createBook(['title' => 'ジャンルなしの本']);

        $response = $this->actingAs($this->user)->get('/books?genre='.$novel->id);

        $response->assertOk()
            ->assertDontSee('リーダブルコード')
            ->assertDontSee('ジャンルなしの本');
        $this->assertSame(['吾輩は猫である'], $this->titlesOf($response));
    }

    public function test_keyword_and_genre_are_combined_with_and(): void
    {
        $novel = Genre::create(['name' => '小説']);
        $tech = Genre::create(['name' => '技術書']);
        $this->createBook(['title' => '吾輩は猫である', 'author' => '夏目漱石'], [$novel->id]);
        $this->createBook(['title' => '漱石の技術書', 'author' => '別の著者'], [$tech->id]);

        $response = $this->actingAs($this->user)->get('/books?keyword=漱石&genre='.$novel->id);

        $this->assertSame(['吾輩は猫である'], $this->titlesOf($response));
    }

    // ---- ソート ----

    private function createBooksWithDates(): void
    {
        $this->createBook(['title' => 'Bravo', 'created_at' => Carbon::parse('2026-02-01')]);
        $this->createBook(['title' => 'Charlie', 'created_at' => Carbon::parse('2026-03-01')]);
        $this->createBook(['title' => 'Alpha', 'created_at' => Carbon::parse('2026-01-01')]);
    }

    public function test_sort_newest(): void
    {
        $this->createBooksWithDates();

        $response = $this->actingAs($this->user)->get('/books?sort=newest');

        $this->assertSame(['Charlie', 'Bravo', 'Alpha'], $this->titlesOf($response));
    }

    public function test_default_sort_is_newest(): void
    {
        $this->createBooksWithDates();

        $response = $this->actingAs($this->user)->get('/books');

        $this->assertSame(['Charlie', 'Bravo', 'Alpha'], $this->titlesOf($response));
    }

    public function test_sort_oldest(): void
    {
        $this->createBooksWithDates();

        $response = $this->actingAs($this->user)->get('/books?sort=oldest');

        $this->assertSame(['Alpha', 'Bravo', 'Charlie'], $this->titlesOf($response));
    }

    public function test_sort_title_ascending(): void
    {
        $this->createBooksWithDates();

        $response = $this->actingAs($this->user)->get('/books?sort=title');

        $this->assertSame(['Alpha', 'Bravo', 'Charlie'], $this->titlesOf($response));
        $response->assertSeeInOrder(['Alpha', 'Bravo', 'Charlie']);
    }

    public function test_sort_rating_by_average_desc_and_unrated_last(): void
    {
        $low = $this->createBook(['title' => 'Low']);
        $high = $this->createBook(['title' => 'High']);
        $this->createBook(['title' => 'Unrated']);
        $middle = $this->createBook(['title' => 'Middle']);

        // 平均: High = 4.5, Middle = 3.0, Low = 1.0, Unrated = レビューなし
        Review::factory()->create(['book_id' => $high->id, 'rating' => 5]);
        Review::factory()->create(['book_id' => $high->id, 'rating' => 4]);
        Review::factory()->create(['book_id' => $middle->id, 'rating' => 3]);
        Review::factory()->create(['book_id' => $low->id, 'rating' => 1]);

        $response = $this->actingAs($this->user)->get('/books?sort=rating');

        $this->assertSame(['High', 'Middle', 'Low', 'Unrated'], $this->titlesOf($response));
    }
}
