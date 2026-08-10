<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RankingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_see_book_ranking()
    {
        $user = User::create([
            'name' => 'testUser',
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
            'genre' => [$genre1ForBook1->id],
        ]);

        $book2 = Book::create([
            'user_id' => $user->id,
            'title' => '人を動かす',
            'author' => 'D・カーネギー',
            'isbn' => '9784422100524',
            'published_at' => '1936-10-01',
            'description' => '人間関係の原則を説いた、自己啓発書の古典的名著。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=2',
            'genres' => [$genre2ForBook2->id, $genre3ForBook2->id],
        ]);

        $reviewForBook1 = Review::create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'rating' => 5,
            'comment' => 'とても面白かったです。',
        ]);

        $reviewForBook2 = Review::create([
            'user_id' => $user->id,
            'book_id' => $book2->id,
            'rating' => 1,
            'comment' => 'とても勉強になりました。',
        ]);

        $response = $this->get(Route('ranking.index'));

        $response->assertViewIs('ranking.index');
        $response->assertSeeInOrder([$book1->title, $book2->title]
        );

    }
}
