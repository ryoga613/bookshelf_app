<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FavoriteControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    public function test_can_see_favorite_books_index_test(): void
    {

        $user = User::create([
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $genre1ForBook1 = Genre::create(['name' => '小説']);
        $genre2ForBook2 = Genre::create(['name' => 'ビジネス']);
        $genre3ForBook2 = Genre::create(['name' => '自己啓発']);

        $favoriteBook = Book::create([
            'user_id' => $user->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_at' => '1905-01-01',
            'description' => '猫の視点から人間社会を風刺的に描いた、夏目漱石の代表作。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=1',
            'genres' => [$genre1ForBook1->id],
        ]);

        $notFavoriteBook = Book::create([
            'user_id' => $user->id,
            'title' => '人を動かす',
            'author' => 'D・カーネギー',
            'isbn' => '9784422100524',
            'published_at' => '1936-10-01',
            'description' => '人間関係の原則を説いた、自己啓発書の古典的名著。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=2',
            'genres' => [$genre2ForBook2->id],
            [$genre3ForBook2->id],
        ]);

        $user->favoriteBooks()->attach($favoriteBook->id);

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertViewIs('favorites.index');

    }

    public function test_cant_see_favorite_books_index_test()
    {
        $user = User::create([
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $genre1ForBook1 = Genre::create(['name' => '小説']);
        $genre2ForBook2 = Genre::create(['name' => 'ビジネス']);
        $genre3ForBook2 = Genre::create(['name' => '自己啓発']);

        $favoriteBook = Book::create([
            'user_id' => $user->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_at' => '1905-01-01',
            'description' => '猫の視点から人間社会を風刺的に描いた、夏目漱石の代表作。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=1',
            'genres' => [$genre1ForBook1->id],
        ]);

        $notFavoriteBook = Book::create([
            'user_id' => $user->id,
            'title' => '人を動かす',
            'author' => 'D・カーネギー',
            'isbn' => '9784422100524',
            'published_at' => '1936-10-01',
            'description' => '人間関係の原則を説いた、自己啓発書の古典的名著。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=2',
            'genres' => [$genre2ForBook2->id, $genre3ForBook2->id],
        ]);

        $user->favoriteBooks()->attach($favoriteBook->id);

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertViewIs('favorites.index');
        $response->assertDontSee($notFavoriteBook->title);
    }

    public function test_favorite_toggle()
    {
        $user = User::create([
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $genre1ForBook1 = Genre::create(['name' => '小説']);

        $book = Book::create([
            'user_id' => $user->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_at' => '1905-01-01',
            'description' => '猫の視点から人間社会を風刺的に描いた、夏目漱石の代表作。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=1',
            'genres' => [$genre1ForBook1->id],
        ]);

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // テーブルに登録されることを確認
        $response = $this->actingAs($user)->post(route('favorites.toggle', $book));

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // テーブルから削除されることを確認
        $response = $this->actingAs($user)->post(route('favorites.toggle', $book));

        $response->assertRedirect();
        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

    }
}
