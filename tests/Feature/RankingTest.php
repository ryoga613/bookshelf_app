<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ランキング（表示・平均評価の降順並び）
 */
class RankingTest extends TestCase
{
    use RefreshDatabase;

    private function bookWithRatings(string $title, array $ratings): Book
    {
        $book = Book::factory()->create(['title' => $title]);
        foreach ($ratings as $rating) {
            Review::factory()->create(['book_id' => $book->id, 'rating' => $rating]);
        }

        return $book;
    }

    public function test_guest_can_see_ranking(): void
    {
        $this->bookWithRatings('吾輩は猫である', [5]);

        $this->get(route('ranking.index'))
            ->assertOk()
            ->assertViewIs('ranking.index')
            ->assertSee('吾輩は猫である');
    }

    public function test_logged_in_user_can_see_ranking(): void
    {
        $this->actingAs(User::factory()->create())->get(route('ranking.index'))->assertOk();
    }

    public function test_books_are_ordered_by_average_rating_desc(): void
    {
        $this->bookWithRatings('Average3', [2, 4]);
        $this->bookWithRatings('Average5', [5, 5]);
        $this->bookWithRatings('Average1', [1]);
        $this->bookWithRatings('Average4', [5, 4, 3, 4]);

        $response = $this->get(route('ranking.index'))->assertOk();

        $this->assertSame(
            ['Average5', 'Average4', 'Average3', 'Average1'],
            $response->viewData('rankedBooks')->pluck('title')->all()
        );
        $response->assertSeeInOrder(['Average5', 'Average4', 'Average3', 'Average1']);
    }

    public function test_average_rating_and_review_count_are_calculated(): void
    {
        $this->bookWithRatings('吾輩は猫である', [5, 4, 4]);

        $book = $this->get(route('ranking.index'))->viewData('rankedBooks')->first();

        $this->assertEqualsWithDelta(4.33, $book->reviews_avg_rating, 0.01);
        $this->assertSame(3, $book->reviews_count);
        $this->get(route('ranking.index'))->assertSee('4.33');
    }

    public function test_ranking_is_limited_to_top_10(): void
    {
        foreach (range(1, 12) as $i) {
            $this->bookWithRatings("Book{$i}", [($i % 5) + 1]);
        }

        $this->assertCount(10, $this->get(route('ranking.index'))->viewData('rankedBooks'));
    }

    public function test_books_without_reviews_are_ranked_last(): void
    {
        Book::factory()->create(['title' => 'NoReview']);
        $this->bookWithRatings('Rated', [1]);

        $titles = $this->get(route('ranking.index'))->viewData('rankedBooks')->pluck('title')->all();

        $this->assertSame('Rated', $titles[0]);
    }

    public function test_empty_ranking_is_displayed_safely(): void
    {
        $this->get(route('ranking.index'))
            ->assertOk()
            ->assertViewHas('rankedBooks', fn ($books) => $books->isEmpty());
    }
}
