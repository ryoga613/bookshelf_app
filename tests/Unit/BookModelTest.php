<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Favorite;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookModelTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    use RefreshDatabase;

    public function test_can_get_reviews(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $reviews = Review::factory()->count(2)->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $booksReviews = $book->reviews;

        $this->assertCount(2, $booksReviews);
        $this->assertTrue($booksReviews->contains($reviews->first()));
        $this->assertTrue($booksReviews->contains($reviews->last()));
    }

    public function test_can_get_favorites()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $favorites = Favorite::factory()->count(2)->create([
            'book_id' => $book->id,
        ]);

        $favoritedBook = $book->favorites;

        $this->assertCount(2, $favoritedBook);
        $this->assertTrue($favoritedBook->contains($favorites->first()));
        $this->assertTrue($favoritedBook->contains($favorites->last()));
    }

    public function test_can_get_genre()
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create();

        $book->genres()->attach($genre->id);

        $bookGenres = $book->genres;

        $this->assertTrue($bookGenres->contains($genre));
    }

    public function test_can_get_favorited_by_users()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $favorites = Favorite::factory()->create([
            'book_id' => $book->id,
            'user_id' => $user->id,
        ]);

        $booksfavoritedByUsers = $book->favoritedByUsers;
        $this->assertTrue($booksfavoritedByUsers->contains($user));

    }
}
