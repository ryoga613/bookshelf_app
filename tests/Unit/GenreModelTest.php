<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreModelTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    use RefreshDatabase;

    public function test_can_get_books(): void
    {
        $genre = Genre::factory()->create();
        $books = Book::factory()->count(2)->create();

        $genre->books()->attach($books->pluck('id'));
        // $genre->attach(['genre_id'=>$genre->id]);

        $booksGenre = $genre->books;
        $this->assertTrue($booksGenre->contains($books->first()));
        $this->assertTrue($booksGenre->contains($books->last()));
    }
}
