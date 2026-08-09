<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReviewLikeControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    public function test_can_store_review_like()
    {
        $user = User::create([
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $genre = Genre::create(['name' => '小説']);
        $book = Book::create([
            'user_id' => $user->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_at' => '1905-01-01',
            'description' => '猫の視点から人間社会を風刺的に描いた、夏目漱石の代表作。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=1',
            'genres' => [$genre->id],
        ]);

        $review = Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => 'とても、勉強になりました。',
        ]);
        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        $response = $this->actingAs($user)->post(route('reviews.like', $review));

        $response->assertRedirect();
        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }

    public function test_can_delete_review_like()
    {
        $user = User::create([
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $genre = Genre::create(['name' => '小説']);
        $book = Book::create([
            'user_id' => $user->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_at' => '1905-01-01',
            'description' => '猫の視点から人間社会を風刺的に描いた、夏目漱石の代表作。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=1',
            'genres' => [$genre->id],
        ]);

        $review = Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => 'とても、勉強になりました。',
        ]);

        ReviewLike::create([
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        $response = $this->actingAs($user)->post(route('reviews.like', $review));

        $response->assertRedirect();
        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }
}
