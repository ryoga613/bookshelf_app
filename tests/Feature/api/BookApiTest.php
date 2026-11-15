<?php

namespace Tests\Feature\api;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 書籍 API（/api/v1/books）の要件確認
 */
class BookApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Genre $novel;

    private Genre $business;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->novel = Genre::create(['name' => '小説']);
        $this->business = Genre::create(['name' => 'ビジネス']);
    }

    private function createBook(array $attributes = [], array $genreIds = []): Book
    {
        $book = Book::factory()->create(array_merge(['user_id' => $this->user->id], $attributes));
        $book->genres()->sync($genreIds);

        return $book;
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_at' => '1905-01-01',
            'description' => '猫の視点から人間社会を風刺的に描いた作品。',
            'image_url' => 'https://example.com/neko.jpg',
            'genre_ids' => [$this->novel->id],
        ], $overrides);
    }

    // ---- GET /api/v1/books ----

    // 1. 200 が返り、data に一覧、meta にページ情報が含まれる
    public function test_index_returns_data_and_meta(): void
    {
        $this->createBook();
        $this->createBook();

        $this->getJson('/api/v1/books')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'title', 'author', 'isbn']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('meta.total', 2);
    }

    // 2. keyword・genre_id で検索・絞り込みができる
    public function test_index_filters_by_keyword_and_genre_id(): void
    {
        $neko = $this->createBook(['title' => '吾輩は猫である', 'author' => '夏目漱石'], [$this->novel->id]);
        $botchan = $this->createBook(['title' => '坊っちゃん', 'author' => '夏目漱石'], [$this->novel->id]);
        $hito = $this->createBook(['title' => '人を動かす', 'author' => 'D・カーネギー'], [$this->business->id]);

        // タイトルで検索
        $this->getJson('/api/v1/books?keyword=猫')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $neko->id);

        // 著者名で検索
        $this->getJson('/api/v1/books?keyword=漱石')
            ->assertOk()->assertJsonCount(2, 'data');

        // ジャンルで絞り込み
        $this->getJson("/api/v1/books?genre_id={$this->business->id}")
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $hito->id);

        // 組み合わせ
        $this->getJson("/api/v1/books?keyword=坊&genre_id={$this->novel->id}")
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $botchan->id);

        // 存在しない genre_id は 422
        $this->getJson('/api/v1/books?genre_id=9999')
            ->assertStatus(422)->assertJsonPath('errors.genre_id.0', '指定されたジャンルが存在しません。');
    }

    // keyword の % _ ! はワイルドカードではなく文字として検索される
    public function test_index_keyword_escapes_like_wildcards(): void
    {
        $percent = $this->createBook(['title' => '100%の努力', 'author' => '山田']);
        $underscore = $this->createBook(['title' => 'snake_case入門', 'author' => '鈴木']);
        $bang = $this->createBook(['title' => 'やった!', 'author' => '田中']);
        $thousand = $this->createBook(['title' => '1000の努力', 'author' => '佐藤']);
        $this->createBook(['title' => 'snakeXcase入門', 'author' => '高橋']);

        foreach ([
            '%' => [$percent->id],
            '0%' => [$percent->id],
            '_' => [$underscore->id],
            'snake_case' => [$underscore->id],
            '!' => [$bang->id],
            '努力' => collect([$percent->id, $thousand->id])->sort()->values()->all(), // 通常の部分一致は従来どおり
        ] as $keyword => $expectedIds) {
            $response = $this->getJson('/api/v1/books?keyword='.urlencode($keyword))->assertOk();
            $actualIds = collect($response->json('data.data') ?? $response->json('data'))->pluck('id')->sort()->values()->all();
            $this->assertSame($expectedIds, $actualIds, "keyword={$keyword}");
        }
    }

    // 3. per_page・page でページごとに正しい件数（デフォルト20、最大100）
    public function test_index_pagination(): void
    {
        Book::factory(25)->create(['user_id' => $this->user->id]);

        // デフォルトは 20 件
        $this->getJson('/api/v1/books')
            ->assertOk()->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total', 25);

        // 2 ページ目は残りの 5 件
        $this->getJson('/api/v1/books?page=2')
            ->assertOk()->assertJsonCount(5, 'data')->assertJsonPath('meta.current_page', 2);

        // per_page を指定
        $this->getJson('/api/v1/books?per_page=10&page=3')
            ->assertOk()->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.current_page', 3)
            ->assertJsonPath('meta.last_page', 3);

        // 最大 100
        $this->getJson('/api/v1/books?per_page=100')
            ->assertOk()->assertJsonCount(25, 'data')->assertJsonPath('meta.per_page', 100);
        $this->getJson('/api/v1/books?per_page=101')
            ->assertStatus(422)->assertJsonPath('errors.per_page.0', '取得件数は100以下で指定してください。');
    }

    // ---- GET /api/v1/books/{book} ----

    // 4. 存在する ID で、ジャンル・レビューを含む詳細が返る
    public function test_show_returns_book_with_genres_and_reviews(): void
    {
        $book = $this->createBook(['title' => '吾輩は猫である'], [$this->novel->id]);
        Review::create(['user_id' => $this->user->id, 'book_id' => $book->id, 'rating' => 5, 'comment' => '面白い']);

        $this->getJson("/api/v1/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $book->id)
            ->assertJsonPath('data.title', '吾輩は猫である')
            ->assertJsonPath('data.genres.0.name', '小説')
            ->assertJsonPath('data.reviews.0.comment', '面白い');
    }

    // 5. 存在しない ID で 404
    public function test_show_not_found(): void
    {
        $this->getJson('/api/v1/books/9999')
            ->assertNotFound()
            ->assertExactJson(['error' => '書籍が見つかりませんでした。']);
    }

    // ---- POST /api/v1/books ----

    // 6. 201 が返り、books と book_genre に保存される
    public function test_store_creates_book_and_genres(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/books', $this->validPayload(['genre_ids' => [$this->novel->id, $this->business->id]]));

        $response->assertCreated()->assertJsonPath('data.title', '吾輩は猫である');

        $bookId = $response->json('data.id');
        $this->assertDatabaseHas('books', ['id' => $bookId, 'isbn' => '9784101010014', 'user_id' => $this->user->id]);
        $this->assertDatabaseHas('book_genre', ['book_id' => $bookId, 'genre_id' => $this->novel->id]);
        $this->assertDatabaseHas('book_genre', ['book_id' => $bookId, 'genre_id' => $this->business->id]);
    }

    // 7. 不正データで 422 と日本語のエラーメッセージ
    public function test_store_invalid_returns_422_in_japanese(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/books', ['isbn' => '123', 'image_url' => 'not-url', 'genre_ids' => [9999]])
            ->assertStatus(422)
            ->assertJsonPath('errors.title.0', 'タイトルは必須です')
            ->assertJsonPath('errors.author.0', '著者名は必須です')
            ->assertJsonPath('errors.isbn.0', 'ISBNは13桁の数字で入力してください。')
            ->assertJsonPath('errors.published_at.0', '出版日は必須です')
            ->assertJsonPath('errors.image_url.0', '画像URLは有効なURL形式で入力してください。')
            ->assertJsonPath('errors.description.0', '概要は必須です。')
            ->assertJsonValidationErrors(['genre_ids.0' => '選択されたジャンルは存在しません。']);

        $this->assertDatabaseCount('books', 0);
    }

    // Accept ヘッダーがなくても 302 ではなく 422 の JSON が返る
    public function test_store_invalid_without_accept_header_returns_json(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->post('/api/v1/books', [])
            ->assertStatus(422)
            ->assertJsonPath('errors.title.0', 'タイトルは必須です');
    }

    // ---- PUT /api/v1/books/{book} ----

    // 8. 200 が返り、books と book_genre が更新される
    public function test_update_updates_book_and_genres(): void
    {
        $book = $this->createBook(['isbn' => '9784101010014'], [$this->novel->id]);

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/books/{$book->id}", $this->validPayload([
                'title' => '坊っちゃん',
                'genre_ids' => [$this->business->id],
            ]))
            ->assertOk()
            ->assertJsonPath('data.title', '坊っちゃん')
            ->assertJsonPath('data.genres.0.id', $this->business->id);

        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => '坊っちゃん']);
        $this->assertDatabaseHas('book_genre', ['book_id' => $book->id, 'genre_id' => $this->business->id]);
        $this->assertDatabaseMissing('book_genre', ['book_id' => $book->id, 'genre_id' => $this->novel->id]);
    }

    // 9. 存在しない ID で 404
    public function test_update_not_found(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/v1/books/9999', $this->validPayload())
            ->assertNotFound()
            ->assertExactJson(['error' => '書籍が見つかりませんでした。']);
    }

    // 10. 不正データで 422 と日本語のエラーメッセージ
    public function test_update_invalid_returns_422_in_japanese(): void
    {
        $book = $this->createBook(['title' => '吾輩は猫である', 'isbn' => '9784101010014'], [$this->novel->id]);
        $other = $this->createBook(['isbn' => '9784422100524']);

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/books/{$book->id}", $this->validPayload([
                'title' => '',
                'isbn' => $other->isbn,
                'published_at' => 'not-a-date',
                'genre_ids' => [],
            ]))
            ->assertStatus(422)
            ->assertJsonPath('errors.title.0', 'タイトルは必須です')
            ->assertJsonPath('errors.isbn.0', 'このISBNは既に使用されています。')
            ->assertJsonPath('errors.published_at.0', '出版日は有効な日付形式で入力してください。')
            ->assertJsonPath('errors.genre_ids.0', 'ジャンルは1つ以上選択してください。');

        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => '吾輩は猫である']);
    }
}
