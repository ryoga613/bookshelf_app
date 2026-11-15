<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 書籍登録・編集画面で、バリデーションエラーのメッセージが画面に表示されることを確認する
 */
class BookFormErrorDisplayTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Genre $genre;

    private Book $book;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->genre = Genre::create(['name' => '小説']);
        $this->book = Book::factory()->create(['user_id' => $this->user->id, 'isbn' => '9784101010014']);
        Book::factory()->create(['user_id' => $this->user->id, 'isbn' => '9784422100524']);
    }

    private function validInput(array $overrides = []): array
    {
        return array_merge([
            'title' => '坊っちゃん',
            'author' => '夏目漱石',
            'isbn' => '9784101010021',
            'published_date' => '1906-04-01',
            'description' => null,
            'image_url' => null,
            'genres' => [$this->genre->id],
        ], $overrides);
    }

    /**
     * 登録画面・編集画面の両方で送信し、リダイレクト後の画面にメッセージが表示されることを確認する
     */
    private function assertMessagesShown(array $input, array $messages, bool $isEdit = true): void
    {
        $screens = [
            '登録' => [route('books.create'), 'post', route('books.store')],
        ];
        if ($isEdit) {
            $screens['編集'] = [route('books.edit', $this->book), 'put', route('books.update', $this->book)];
        }

        foreach ($screens as $name => [$from, $method, $action]) {
            $response = $this->actingAs($this->user)
                ->from($from)
                ->followingRedirects()
                ->{$method}($action, $input);

            $response->assertOk();
            foreach ($messages as $message) {
                $response->assertSee($message, false);
            }
        }
    }

    // a. 必須項目が未入力の場合
    public function test_a_required_fields(): void
    {
        $this->assertMessagesShown(
            $this->validInput(['title' => '', 'author' => '', 'isbn' => '', 'published_date' => '', 'genres' => null]),
            ['タイトルは必須です。', '著者名は必須です。', 'ISBNは必須です。', '出版日は必須です。', 'ジャンルは1つ以上選択してください。']
        );
    }

    // b. タイトル・著者名・画像URLが255文字を超えた場合
    public function test_b_over_255_chars(): void
    {
        $this->assertMessagesShown(
            $this->validInput([
                'title' => str_repeat('あ', 256),
                'author' => str_repeat('あ', 256),
                'image_url' => 'https://example.com/'.str_repeat('a', 240),
            ]),
            ['タイトルは255文字以内で入力してください。', '著者名は255文字以内で入力してください。', '画像URLは255文字以内で入力してください。']
        );
    }

    // c. ISBNが13桁でない場合
    public function test_c_isbn_not_13(): void
    {
        $this->assertMessagesShown($this->validInput(['isbn' => '978410101002']), ['ISBNは13文字で入力してください。']);
        $this->assertMessagesShown($this->validInput(['isbn' => '97841010100211']), ['ISBNは13文字で入力してください。']);
    }

    // d. ISBNが重複している場合（編集画面では他の本のISBN）
    public function test_d_isbn_duplicated(): void
    {
        $this->assertMessagesShown($this->validInput(['isbn' => '9784422100524']), ['このISBNは既に使用されています。']);
    }

    // e. 出版日が有効な日付形式でない場合
    public function test_e_invalid_date(): void
    {
        $this->assertMessagesShown($this->validInput(['published_date' => '2026-13-45']), ['出版日は有効な日付形式で入力してください。']);
    }

    // f. 画像URLがURL形式でない場合
    public function test_f_invalid_url(): void
    {
        $this->assertMessagesShown($this->validInput(['image_url' => 'not-a-url']), ['画像URLは有効なURL形式で入力してください。']);
    }

    // g. ジャンルが未選択の場合
    public function test_g_no_genre(): void
    {
        $input = $this->validInput();
        unset($input['genres']);
        $this->assertMessagesShown($input, ['ジャンルは1つ以上選択してください。']);
    }
}
