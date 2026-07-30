<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
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

    public function test_can_search_books_by_title(): void
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

        $response = $this->actingAs($user)->get('/books', ['keyword' => '吾輩は猫である']);

        $response->assertSee('吾輩は猫である');
        $response->assertSee('夏目漱石');
    }

    public function test_can_search_books_by_author(): void
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

        $response = $this->actingAs($user)->get('/books', ['keyword' => '夏目漱石']);

        $response->assertSee('吾輩は猫である');
        $response->assertSee('夏目漱石');
    }

    public function test_can_search_books_by_genres(): void
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

        $response = $this->actingAs($user)->get('/books', ['genre' => '小説']);

        $response->assertSee('吾輩は猫である');
        $response->assertSee('夏目漱石');
    }

    public function test_can_sort_newest()
    {

        $user = User::create([
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        $book1 = Book::create([
            'user_id' => $user->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_at' => '1905-01-01',
            'description' => '猫の視点から人間社会を風刺的に描いた、夏目漱石の代表作。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=1',
            'genres' => ['小説'],
        ]);

        $book2 = Book::create([
            'user_id' => $user->id,
            'title' => '人を動かす',
            'author' => 'D・カーネギー',
            'isbn' => '9784422100524',
            'published_at' => '1936-10-01',
            'description' => '人間関係の原則を説いた、自己啓発書の古典的名著。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=2',
            'genres' => ['ビジネス', '自己啓発'],
        ]);
        $book3 = Book::create([
            'user_id' => $user->id,
            'title' => 'リーダブルコード',
            'author' => 'Dustin Boswell',
            'isbn' => '9784873115658',
            'published_at' => '2012-06-23',
            'description' => '読みやすく保守しやすいコードを書くための実践的なテクニックを解説した技術書。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=3',
            'genres' => ['技術書'],
        ]);

        $response = $this->actingAs($user)->get('/books', ['sort' => 'newest']);

        $response->assertSeeInOrder([
            '吾輩は猫である',
            '人を動かす',
            'リーダブルコード',
        ]);
    }

    public function test_can_sort_oldest()
    {
        $user = User::create([
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        $book1 = Book::create([
            'user_id' => $user->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_at' => '1905-01-01',
            'description' => '猫の視点から人間社会を風刺的に描いた、夏目漱石の代表作。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=1',
            'genres' => ['小説'],
        ]);

        $book2 = Book::create([
            'user_id' => $user->id,
            'title' => '人を動かす',
            'author' => 'D・カーネギー',
            'isbn' => '9784422100524',
            'published_at' => '1936-10-01',
            'description' => '人間関係の原則を説いた、自己啓発書の古典的名著。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=2',
            'genres' => ['ビジネス', '自己啓発'],
        ]);

        $book3 = Book::create([
            'user_id' => $user->id,
            'title' => 'リーダブルコード',
            'author' => 'Dustin Boswell',
            'isbn' => '9784873115658',
            'published_at' => '2012-06-23',
            'description' => '読みやすく保守しやすいコードを書くための実践的なテクニックを解説した技術書。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=3',
            'genres' => ['技術書'],
        ]);

        $response = $this->actingAs($user)->get('/books?sort=oldest');

        $response->assertSeeInOrder([
            '吾輩は猫である',
            '人を動かす',
            'リーダブルコード',
        ]);
    }

    public function test_can_sort_title()
    {
        $user = User::create([
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        $book1 = Book::create([
            'user_id' => $user->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_at' => '1905-01-01',
            'description' => '猫の視点から人間社会を風刺的に描いた、夏目漱石の代表作。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=1',
            'genres' => ['小説'],
        ]);

        $book2 = Book::create([
            'user_id' => $user->id,
            'title' => '人を動かす',
            'author' => 'D・カーネギー',
            'isbn' => '9784422100524',
            'published_at' => '1936-10-01',
            'description' => '人間関係の原則を説いた、自己啓発書の古典的名著。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=2',
            'genres' => ['ビジネス', '自己啓発'],
        ]);

        $book3 = Book::create([
            'user_id' => $user->id,
            'title' => 'リーダブルコード',
            'author' => 'Dustin Boswell',
            'isbn' => '9784873115658',
            'published_at' => '2012-06-23',
            'description' => '読みやすく保守しやすいコードを書くための実践的なテクニックを解説した技術書。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=3',
            'genres' => ['技術書'],
        ]);

        $response = $this->actingAs($user)->get('/books?sort=title');

        $response->assertSeeInOrder([
            '吾輩は猫である',
            '人を動かす',
            'リーダブルコード',
        ]);
    }

    public function test_can_sort_rating()
    {

        $user = User::create([
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        $book1 = Book::create([
            'user_id' => $user->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_at' => '1905-01-01',
            'description' => '猫の視点から人間社会を風刺的に描いた、夏目漱石の代表作。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=1',
            'genres' => ['小説'],
        ]);

        $book2 = Book::create([
            'user_id' => $user->id,
            'title' => '人を動かす',
            'author' => 'D・カーネギー',
            'isbn' => '9784422100524',
            'published_at' => '1936-10-01',
            'description' => '人間関係の原則を説いた、自己啓発書の古典的名著。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=2',
            'genres' => ['ビジネス', '自己啓発'],
        ]);

        $book3 = Book::create([
            'user_id' => $user->id,
            'title' => 'リーダブルコード',
            'author' => 'Dustin Boswell',
            'isbn' => '9784873115658',
            'published_at' => '2012-06-23',
            'description' => '読みやすく保守しやすいコードを書くための実践的なテクニックを解説した技術書。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=3',
            'genres' => ['技術書'],
        ]);

        $reviewForBook1 = Review::create([
            'book_id' => $book1->id,
            'user_id' => $user->id,
            'rating' => 3,
        ]);
        $reviewForBook2 = Review::create([
            'book_id' => $book2->id,
            'user_id' => $user->id,
            'rating' => 5,
        ]);
        $reviewForBook3 = Review::create([
            'book_id' => $book3->id,
            'user_id' => $user->id,
            'rating' => 1,
        ]);
        $response = $this->actingAs($user)->get('/books?sort=rating');

        $response->assertSeeInOrder([
            '人を動かす',
            '吾輩は猫である',
            'リーダブルコード',
        ]);
    }

    public function test_can_show_book_detail()
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

        $response = $this->actingAs($user)->get(route('books.show', [$book->id]));

        $response->assertSee('吾輩は猫である', '夏目漱石');
    }

    public function test_user_can_see_book_edit()
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

        $response = $this->actingAs($user)->get(route('books.edit', [$book->id]));

        $response->assertSee('吾輩は猫である', '夏目漱石');
    }

    public function test_user_can_store_book()
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
            'genres' => [$genre->id],
        ];

        $response = $this->actingAs($user)->post(route('books.store', $book));

        $this->assertDatabaseHas('books', [
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
        ]);
    }

    public function test_user_can_update_book()
    {
        $user = User::create([
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $genre1ForBook1 = Genre::create(['name' => '小説']);
        $genre2ForBook2 = Genre::create(['name' => 'ビジネス']);
        $genre3ForBook2 = Genre::create(['name' => '自己啓発']);

        $book1 = Book::create([
            'user_id' => $user->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_at' => '1905-01-01',
            'description' => '猫の視点から人間社会を風刺的に描いた、夏目漱石の代表作。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=1',
            'genres' => [$genre1ForBook1->id],
        ]);

        $this->assertDatabaseHas('books',
            ['title' => '吾輩は猫である',
                'author' => '夏目漱石',
                'isbn' => '9784101010014', ]);

        $book2 = Book::create([
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

        $this->actingAs($user)->post(route('books.update', $book2));

        $this->assertDatabaseHas('books', [
            'title' => '人を動かす',
            'author' => 'D・カーネギー',
            'isbn' => '9784422100524',
        ]);

    }

    public function test_user_can_destroy_book()
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

        $this->assertDataBaseHas('books', [
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
        ]);

        $response = $this->actingAs($user)->delete(route('books.destroy', $book));

        $this->assertDataBaseMissing('books', [
            'title' => '人を動かす',
            'author' => 'D・カーネギー',
            'isbn' => '9784422100524',
        ]);
    }
}
