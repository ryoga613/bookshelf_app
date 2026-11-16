<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * マイ読書レポート（/reports）
 */
class MyReportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function review(int $rating, ?Book $book = null): Review
    {
        return Review::factory()->create([
            'user_id' => $this->user->id,
            'book_id' => ($book ?? Book::factory()->create())->id,
            'rating' => $rating,
        ]);
    }

    private function stats(): array
    {
        return $this->actingAs($this->user)->get('/reports')
            ->assertOk()
            ->assertViewIs('reports.index')
            ->viewData('stats');
    }

    public function test_summary(): void
    {
        $book = Book::factory()->create();
        $this->review(5, $book);
        $this->review(3, $book); // 同じ本への2件目（読了冊数は1冊として数える）
        $this->review(4);

        // 他のユーザーのレビューは集計に含めない
        Review::factory()->create(['rating' => 1]);

        $summary = $this->stats()['summary'];

        $this->assertSame(3, $summary['total_reviews']);
        $this->assertSame(2, $summary['books_read']);
        $this->assertEqualsWithDelta(4.0, $summary['average_rating'], 0.001);
    }

    public function test_rating_distribution(): void
    {
        foreach ([5, 5, 4, 2, 2, 2] as $rating) {
            $this->review($rating);
        }

        // 添字 0〜4 が ★1〜★5 の件数
        $this->assertSame([0, 3, 0, 1, 2], $this->stats()['rating_distribution']->all());
    }

    public function test_top_rated_books_are_top5_with_rating_4_or_more(): void
    {
        foreach ([4, 5, 3, 4, 5, 4, 4, 1] as $rating) {
            $this->review($rating);
        }

        $top = $this->stats()['top_rated_books'];

        $this->assertCount(5, $top);
        $this->assertSame([5, 5, 4, 4, 4], $top->pluck('rating')->all());
        $this->assertSame(['id', 'title', 'author', 'rating'], array_keys($top->first()));
    }

    public function test_genre_ratings(): void
    {
        $novel = Genre::create(['name' => '小説']);
        $business = Genre::create(['name' => 'ビジネス']);

        $novelBook = Book::factory()->create();
        $novelBook->genres()->sync([$novel->id]);
        $bothBook = Book::factory()->create();
        $bothBook->genres()->sync([$novel->id, $business->id]);

        $this->review(5, $novelBook);
        $this->review(3, $bothBook); // 2ジャンルそれぞれに集計される

        $genres = $this->stats()['genre_ratings'];

        $this->assertCount(2, $genres);
        // 平均評価の高い順
        $this->assertSame('小説', $genres[0]['name']);
        $this->assertSame(2, $genres[0]['count']);
        $this->assertEqualsWithDelta(4.0, $genres[0]['average_rating'], 0.001);
        $this->assertSame('ビジネス', $genres[1]['name']);
        $this->assertSame(1, $genres[1]['count']);
        $this->assertEqualsWithDelta(3.0, $genres[1]['average_rating'], 0.001);
    }

    public function test_genre_ratings_are_limited_to_top5(): void
    {
        foreach (range(1, 6) as $i) {
            $genre = Genre::create(['name' => "ジャンル{$i}"]);
            $book = Book::factory()->create();
            $book->genres()->sync([$genre->id]);
            $this->review(min($i, 5), $book);
        }

        $this->assertCount(5, $this->stats()['genre_ratings']);
    }

    public function test_user_without_reviews_sees_report_safely(): void
    {
        $response = $this->actingAs($this->user)->get('/reports');

        $response->assertOk()->assertSee('マイ読書レポート');

        $stats = $response->viewData('stats');
        $this->assertSame(0, $stats['summary']['total_reviews']);
        $this->assertSame(0, $stats['summary']['books_read']);
        $this->assertEquals(0, $stats['summary']['average_rating']);
        $this->assertSame([0, 0, 0, 0, 0], $stats['rating_distribution']->all());
        $this->assertCount(0, $stats['top_rated_books']);
        $this->assertCount(0, $stats['genre_ratings']);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/reports')->assertRedirect(route('login'));
    }
}
