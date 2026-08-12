<?php

namespace Tests\Feature\api;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BookControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    public function test_can_see_index_json(): void
    {
        $user = User::create([
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $genre = ([
            'name' => '小説',
        ]);

        $book = Book::create([
            'user_id' => $user->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_at' => '1905-01-01',
            'description' => '猫の視点から人間社会を風刺的に描いた、夏目漱石の代表作。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=1',
            'genres' => $genre,
        ]);

        $response = $this->getJson('api/v1/books');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_can_store_book_json()
    {
        $user = User::create([
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $genre = Genre::create(['name' => '小説']);
        $book = [
            'user_id' => $user->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_at' => '1905-01-01',
            'description' => '猫の視点から人間社会を風刺的に描いた、夏目漱石の代表作。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=1',
            'genre_ids' => [$genre->id],
        ];

        $response = $this->actingAs($user)->postJson('api/v1/books', $book);

        $response->assertStatus(201);
        $this->assertDatabaseHas('books', [
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
        ]);
    }

    public function test_can_show_book_detail()
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
            'genre_ids' => [$genre->id],
        ]);

        $response = $this->actingAs($user)->getJson("api/v1/books/{$book->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.title', '吾輩は猫である');

    }

    public function test_can_update_book_json()
    {

        $user = User::create([
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $genre = Genre::create(['name' => '小説']);
        $beforeBook = Book::create([
            'user_id' => $user->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_at' => '1905-01-01',
            'description' => '猫の視点から人間社会を風刺的に描いた、夏目漱石の代表作。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=1',
            'genre_ids' => [$genre->id],
        ]);

        $afterBook = [
            'user_id' => $user->id,
            'title' => 'ぼっちゃん',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_at' => '1905-01-01',
            'description' => '猫の視点から人間社会を風刺的に描いた、夏目漱石の代表作。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=1',
            'genre_ids' => [$genre->id],
        ];

        $response = $this->putJson("/api/v1/books/{$beforeBook->id}", $afterBook);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'ぼっちゃん');
    }

    public function test_delete_book_json()
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
            'genre_ids' => [$genre->id],
        ]);

        $response = $this->actingAs($user)->deleteJson("api/v1/books/{$book->id}");

        $this->assertDatabaseMissing('books',['title'=>'吾輩は猫である'])
        ->assertDatabaseMissing('books', ['id' => $book->id]);
    }
}
