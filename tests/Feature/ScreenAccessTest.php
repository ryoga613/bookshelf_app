<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 画面アクセス（書籍一覧の表示・書籍登録フォームの認証ガード）
 */
class ScreenAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_see_book_index(): void
    {
        $book = Book::factory()->create(['title' => '吾輩は猫である', 'author' => '夏目漱石']);

        $this->get(route('books.index'))
            ->assertOk()
            ->assertViewIs('books.index')
            ->assertViewHas('books', fn ($books) => $books->pluck('id')->all() === [$book->id])
            ->assertSee('吾輩は猫である')
            ->assertSee('夏目漱石');
    }

    public function test_logged_in_user_can_see_book_index(): void
    {
        Book::factory()->create(['title' => '吾輩は猫である']);

        $this->actingAs(User::factory()->create())
            ->get(route('books.index'))
            ->assertOk()
            ->assertSee('吾輩は猫である');
    }

    public function test_book_index_is_paginated_by_10(): void
    {
        Book::factory(15)->create();

        $response = $this->get(route('books.index'))->assertOk();

        $this->assertCount(10, $response->viewData('books'));
        $this->assertSame(15, $response->viewData('books')->total());

        $this->assertCount(5, $this->get(route('books.index', ['page' => 2]))->viewData('books'));
    }

    public function test_book_index_shows_genres_for_filter(): void
    {
        Genre::create(['name' => '小説']);
        Genre::create(['name' => '技術書']);

        $this->get(route('books.index'))
            ->assertOk()
            ->assertViewHas('genres', fn ($genres) => $genres->count() === 2)
            ->assertSee('小説')
            ->assertSee('技術書');
    }

    public function test_guest_is_redirected_from_create_form(): void
    {
        $this->get(route('books.create'))->assertRedirect(route('login'));
    }

    public function test_guest_cannot_store_book(): void
    {
        $genre = Genre::create(['name' => '小説']);

        $this->post(route('books.store'), [
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'genres' => [$genre->id],
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('books', 0);
    }

    public function test_logged_in_user_can_see_create_form(): void
    {
        Genre::create(['name' => '小説']);

        $this->actingAs(User::factory()->create())
            ->get(route('books.create'))
            ->assertOk()
            ->assertViewIs('books.create')
            ->assertViewHas('genres')
            ->assertSee('小説');
    }
}
