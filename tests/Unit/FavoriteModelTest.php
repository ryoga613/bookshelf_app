<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Book;
use App\Models\Favorite;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FavoriteModelTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    use RefreshDatabase;

    public function test_can_get_user(): void
    {
        $user = User::factory()->create();
        $favorite = Favorite::factory()->create([
            'user_id' =>$user->id,
        ]);
 
        $favoritesUser = $favorite->user;
        $this->assertTrue($favoritesUser->is($user));

    }

    public function test_can_get_book()
    {
        $book = Book::factory()->create();
        $favorite = Favorite::factory()->create([
            'book_id' =>$book->id,
        ]);

        $favoritedBook = $favorite->book;
                $this->assertTrue($favoritedBook->is($book));


    }
}
