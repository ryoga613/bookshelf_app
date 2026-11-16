<?php

namespace Tests\Feature;

use App\Models\User;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request as PsrRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ISBN 検索（/books/isbn/{isbn}）— Google Books API は Http::fake() でモック化
 */
class IsbnSearchTest extends TestCase
{
    use RefreshDatabase;

    private const API_URL = 'https://www.googleapis.com/books/v1/volumes*';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function search(string $isbn)
    {
        return $this->actingAs($this->user)->getJson(route('books.isbn', $isbn));
    }

    // ---- 正常系 ----

    public function test_returns_book_info_from_google_books(): void
    {
        Http::fake([
            self::API_URL => Http::response([
                'totalItems' => 1,
                'items' => [[
                    'volumeInfo' => [
                        'title' => '吾輩は猫である',
                        'authors' => ['夏目漱石', '別の著者'],
                        'description' => '猫の視点から描いた作品。',
                        'publishedDate' => '1905-01-01',
                        'imageLinks' => ['thumbnail' => 'https://example.com/neko.jpg'],
                    ],
                ]],
            ]),
        ]);

        $this->search('9784101010014')
            ->assertOk()
            ->assertExactJson([
                'title' => '吾輩は猫である',
                'author' => '夏目漱石, 別の著者',
                'description' => '猫の視点から描いた作品。',
                'image_url' => 'https://example.com/neko.jpg',
                'published_date' => '1905-01-01',
            ]);

        Http::assertSent(fn (Request $request) => $request['q'] === 'isbn:9784101010014');
    }

    public function test_missing_fields_are_returned_as_null(): void
    {
        Http::fake([
            self::API_URL => Http::response(['items' => [['volumeInfo' => ['title' => 'タイトルだけの本']]]]),
        ]);

        $this->search('9784101010014')
            ->assertOk()
            ->assertJson([
                'title' => 'タイトルだけの本',
                'author' => null,
                'description' => null,
                'image_url' => null,
                'published_date' => null,
            ]);
    }

    // ---- 異常系: バリデーション ----

    public function test_invalid_isbn_returns_422_without_calling_api(): void
    {
        Http::fake();

        foreach (['978410101001', '97841010100145', 'abcdefghijklm', '978-4101010014'] as $isbn) {
            $this->search($isbn)
                ->assertStatus(422)
                ->assertJson(['error' => 'ISBNは13桁の数字で入力してください。']);
        }

        Http::assertNothingSent();
    }

    // ---- 異常系: 未検出 ----

    public function test_returns_404_when_book_is_not_found(): void
    {
        Http::fake([
            self::API_URL => Http::response(['kind' => 'books#volumes', 'totalItems' => 0]),
        ]);

        $this->search('9780000000000')
            ->assertNotFound()
            ->assertJson(['error' => '該当する書籍が見つかりませんでした。']);
    }

    // ---- 異常系: 通信エラー ----

    public function test_returns_502_when_api_returns_server_error(): void
    {
        Http::fake([self::API_URL => Http::response('Internal Server Error', 500)]);

        $this->search('9784101010014')
            ->assertStatus(502)
            ->assertJson(['error' => '書籍情報の取得に失敗しました。']);
    }

    public function test_returns_429_when_api_rate_limit_exceeded(): void
    {
        Http::fake([self::API_URL => Http::response(['error' => 'rateLimitExceeded'], 429)]);

        $this->search('9784101010014')
            ->assertStatus(429)
            ->assertJson(['error' => 'Google Books APIの利用上限に達しました。時間をおいて再度お試しください。']);
    }

    public function test_returns_503_when_connection_fails(): void
    {
        Http::fake(function (Request $request) {
            throw new ConnectException('Connection timed out', new PsrRequest('GET', $request->url()));
        });

        $this->search('9784101010014')
            ->assertStatus(503)
            ->assertJson(['error' => '書籍情報の取得中に通信エラーが発生しました。時間をおいて再度お試しください。']);
    }

    // ---- 認証 ----

    public function test_guest_cannot_use_isbn_search(): void
    {
        Http::fake();

        $this->getJson(route('books.isbn', '9784101010014'))->assertUnauthorized();

        Http::assertNothingSent();
    }
}
