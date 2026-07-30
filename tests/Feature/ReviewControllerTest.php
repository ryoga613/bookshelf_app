<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_can_store_review(): void
    {
        $user = User::create([
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        $book = Book::create([
            'user_id' => $user->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_at' => '1905-01-01',
            'description' => '猫の視点から人間社会を風刺的に描いた、夏目漱石の代表作。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=1',
            'genres' => ['小説'],
        ]);

        $review = ([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => 'とても、勉強になりました。',
        ]);

        $response = $this->actingAs($user)->post(route('reviews.store', $book), $review);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => 'とても、勉強になりました。',
        ]);
    }

    public function test_can_see_review_edit()
    {
        $user = User::create([
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        $book = Book::create([
            'user_id' => $user->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_at' => '1905-01-01',
            'description' => '猫の視点から人間社会を風刺的に描いた、夏目漱石の代表作。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=1',
            'genres' => ['小説'],
        ]);

        $review = Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => 'とても、勉強になりました。',
        ]);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => 'とても、勉強になりました。',
        ]);

        $response = $this->actingAs($user)->get(route('reviews.edit', $review));

        $response->assertViewIs('reviews.edit');
    }

    public function test_can_see_review_update()
    {
        $user = User::create([
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        $book = Book::create([
            'user_id' => $user->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_at' => '1905-01-01',
            'description' => '猫の視点から人間社会を風刺的に描いた、夏目漱石の代表作。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=1',
            'genres' => ['小説'],
        ]);

        $review1 = Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => 'とても、勉強になりました。',
        ]);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => 'とても、勉強になりました。',
        ]);

        $review2 = ([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても面白かったです。',
        ]);

        $this->actingAs($user)->put(route('reviews.update', $review1), $review2);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても面白かったです。',
        ]);
    }

    public function test_can_see_review_delete()
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

        $this->assertDatabaseHas('reviews', ['comment' => 'とても、勉強になりました。']);

        $response = $this->actingAs($user)->delete(route('reviews.destroy', $review));

        $this->assertDatabaseMissing('reviews', ['rating' => 3, 'comment' => 'とても、勉強になりました。',
        ]);
    }
}
