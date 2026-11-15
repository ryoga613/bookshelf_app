<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Genre $genre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->genre = Genre::create(['name' => '小説']);
    }

    private function validInput(array $overrides = []): array
    {
        return array_merge([
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_date' => '1905-01-01',
            'description' => null,
            'image_url' => null,
            'genres' => [$this->genre->id],
        ], $overrides);
    }

    public function test_store_saves_published_date_to_published_at(): void
    {
        $this->actingAs($this->user)->post(route('books.store'), $this->validInput())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('books', ['isbn' => '9784101010014', 'published_at' => '1905-01-01']);
    }

    public function test_store_requires_fields(): void
    {
        $this->actingAs($this->user)->post(route('books.store'), [])
            ->assertSessionHasErrors([
                'title' => 'タイトルは必須です。',
                'author' => '著者名は必須です。',
                'isbn' => 'ISBNは必須です。',
                'published_date' => '出版日は必須です。',
                'genres' => 'ジャンルは1つ以上選択してください。',
            ]);
    }

    public function test_store_rejects_invalid_values(): void
    {
        $this->actingAs($this->user)->post(route('books.store'), $this->validInput([
            'title' => str_repeat('あ', 256),
            'author' => str_repeat('あ', 256),
            'isbn' => '978410101001',
            'published_date' => 'not-a-date',
            'image_url' => 'not-a-url',
            'genres' => [999],
        ]))->assertSessionHasErrors([
            'title' => 'タイトルは255文字以内で入力してください。',
            'author' => '著者名は255文字以内で入力してください。',
            'isbn' => 'ISBNは13文字で入力してください。',
            'published_date' => '出版日は有効な日付形式で入力してください。',
            'image_url' => '画像URLは有効なURL形式で入力してください。',
            'genres.0' => '選択されたジャンルは存在しません。',
        ]);
    }

    public function test_image_url_max_255(): void
    {
        $this->actingAs($this->user)->post(route('books.store'), $this->validInput([
            'image_url' => 'https://example.com/'.str_repeat('a', 240),
        ]))->assertSessionHasErrors(['image_url' => '画像URLは255文字以内で入力してください。']);
    }

    public function test_store_rejects_duplicate_isbn(): void
    {
        Book::factory()->create(['user_id' => $this->user->id, 'isbn' => '9784101010014']);

        $this->actingAs($this->user)->post(route('books.store'), $this->validInput())
            ->assertSessionHasErrors(['isbn' => 'このISBNは既に使用されています。']);
    }

    public function test_update_ignores_own_isbn_but_rejects_others(): void
    {
        $book = Book::factory()->create(['user_id' => $this->user->id, 'isbn' => '9784101010014']);
        Book::factory()->create(['user_id' => $this->user->id, 'isbn' => '9784422100524']);

        $this->actingAs($this->user)
            ->put(route('books.update', $book), $this->validInput(['title' => '坊っちゃん']))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => '坊っちゃん', 'published_at' => '1905-01-01']);

        $this->actingAs($this->user)
            ->put(route('books.update', $book), $this->validInput(['isbn' => '9784422100524']))
            ->assertSessionHasErrors(['isbn' => 'このISBNは既に使用されています。']);
    }
}
