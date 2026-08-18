<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Models\Book;

class ReadingPlanModelTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    use RefreshDatabase;

    public function test_can_get_user(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->create([
            'user_id'=>$user->id,
        ]);

        $readingPlansUser = $readingPlan->user;
        $this->assertTrue($readingPlansUser->is($user));
    }

     public function test_can_get_book(): void
    {
        $book = Book::factory()->create();
        $readingPlan = ReadingPlan::factory()->create([
            'book_id'=>$book->id,
        ]);

        $readingPlansBook = $readingPlan->book;
        $this->assertTrue($readingPlansBook->is($book));
    }
}
