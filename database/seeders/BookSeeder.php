<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Book;
use App\Models\Genre;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        $books = [
            [
                'title' => '吾輩は猫である',
                'author' => '夏目漱石',
                'isbn' => '9784101010014',
                'published_at' => '1905-01-01',
                'description' => '猫の視点から人間社会を風刺的に描いた、夏目漱石の代表作。',
                'image_url'=>'https://placehold.co/200x300/e2e8f0/475569?text=1',
                'genres' => ['小説'],
            ],
            [
                'title' => '人を動かす',
                'author' => 'D・カーネギー',
                'isbn' => '9784422100524',
                'published_at' => '1936-10-01',
                'description' => '人間関係の原則を説いた、自己啓発書の古典的名著。',
                'image_url'=>'https://placehold.co/200x300/e2e8f0/475569?text=2',
                'genres' => ['ビジネス', '自己啓発'],
            ],
            [
                'title' => 'リーダブルコード',
                'author' => 'Dustin Boswell',
                'isbn' => '9784873115658',
                'published_at' => '2012-06-23',
                'description' => '読みやすく保守しやすいコードを書くための実践的なテクニックを解説した技術書。',
                'image_url'=>'https://placehold.co/200x300/e2e8f0/475569?text=3',
                'genres' => ['技術書'],
            ],
            [
                'title' => '7つの習慣',
                'author' => 'スティーブン・R・コヴィー',
                'isbn' => '9784863940246',
                'published_at' => '2013-08-30',
                'description' => '人格主義に基づいた自己実現のための7つの原則を紹介する、ビジネス書の定番。',
                'image_url'=>'https://placehold.co/200x300/e2e8f0/475569?text=4',
                'genres' => ['ビジネス', '自己啓発'],
            ],
            [
                'title' => '坊っちゃん',
                'author' => '夏目漱石',
                'isbn' => '9784101010021',
                'published_at' => '1906-04-01',
                'description' => '江戸っ子気質の青年教師が、四国の中学校で奮闘する姿を描いた夏目漱石の代表作。',
                'image_url'=>'https://placehold.co/200x300/e2e8f0/475569?text=5',
                'genres' => ['小説'],
            ],
            [
                'title' => 'サピエンス全史',
                'author' => 'ユヴァル・ノア・ハラリ',
                'isbn' => '9784309226712',
                'published_at' => '2016-09-08',
                'description' => '認知革命・農業革命・科学革命という切り口から、人類の歴史を読み解く壮大な人類史。',
                'image_url'=>'https://placehold.co/200x300/e2e8f0/475569?text=6',
                'genres' => ['歴史', '科学'],
            ],
            [
                'title' => 'Clean Code',
                'author' => 'Robert C. Martin',
                'isbn' => '9784048930598',
                'published_at' => '2017-12-18',
                'description' => '保守性の高い高品質なコードを書くための原則とプラクティスをまとめた技術書。',
                'image_url'=>'https://placehold.co/200x300/e2e8f0/475569?text=7',
                'genres' => ['技術書'],
            ],
            [
                'title' => '嫌われる勇気',
                'author' => '岸見一郎・古賀史健',
                'isbn' => '9784478025819',
                'published_at' => '2013-12-13',
                'description' => 'アドラー心理学を対話形式でわかりやすく解説した、ベストセラーの自己啓発書。',
                'image_url'=>'https://placehold.co/200x300/e2e8f0/475569?text=8',
                'genres' => ['自己啓発'],
            ],
            [
                'title' => '火花',
                'author' => '又吉直樹',
                'isbn' => '9784163902302',
                'published_at' => '2015-03-11',
                'description' => 'お笑い芸人を目指す青年たちの葛藤と友情を描いた、芥川賞受賞作。',
                'image_url'=>'https://placehold.co/200x300/e2e8f0/475569?text=9',
                'genres' => ['小説'],
            ],
            [
                'title' => 'FACTFULNESS',
                'author' => 'ハンス・ロスリング',
                'isbn' => '9784822289607',
                'published_at' => '2019-01-11',
                'description' => 'データに基づいて世界を正しく見るための思考法を説く、ベストセラー。',
                'image_url'=>'https://placehold.co/200x300/e2e8f0/475569?text=10',
                'genres' => ['ビジネス', '科学'],
            ],
            [
                'title' => 'コンテナ物語',
                'author' => 'マルク・レビンソン',
                'isbn' => '9784822251468',
                'published_at' => '2007-01-18',
                'description' => '海上輸送コンテナがもたらした世界経済への革命的影響を描いたノンフィクション。',
                'image_url'=>'https://placehold.co/200x300/e2e8f0/475569?text=11',
                'genres' => ['ビジネス', '歴史'],
            ],
        ];

        foreach($books as $index => $book)
            {
                $number = $index + 1 ;
                $genreNames = $book['genres'];
                unset($book['genres']);

                $newBook = Book::firstOrCreate([
                    'title'=>$book['title'],
                    'author'=>$book['author'],
                    'isbn'=>$book['isbn'],
                    'published_at'=>$book['published_at'],
                    'image_url'=>$book['image_url'],
                    'description'=>$book['description'],
                    'user_id' => $users->random()->id
                    ]
                );
                $genreIds = Genre::whereIn('name', $genreNames)->pluck('id');
                    $newBook->genres()->sync($genreIds);
            }
    }
}
