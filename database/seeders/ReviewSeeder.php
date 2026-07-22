<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Book;
use App\Models\Review;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::orderBy('id')->take(5)->get();
        $books = Book::orderBy('id')->take(11)->get();
 
        $reviewsByBook = [
            // 1冊目（2件）
            [
                ['user' => 0, 'rating' => 1, 'comment' => '読み始めたら止まらず、一気に読了しました。伏線の回収が見事で、読後感も清々しかったです。'],
                ['user' => 1, 'rating' => 2, 'comment' => 'テンポよく読めましたが、後半の展開がやや急ぎ足に感じました。それでも十分楽しめる一冊です。'],
            ],
            // 2冊目（2件）
            [
                ['user' => 2, 'rating' => 3, 'comment' => '文章は丁寧でしたが、中盤で少し中だるみを感じました。設定自体は面白かったです。'],
                ['user' => 3, 'rating' => 4, 'comment' => 'キャラクターの心理描写が秀逸で、感情移入しながら読み進められました。おすすめです。'],
            ],
            // 3冊目（2件）
            [
                ['user' => 4, 'rating' => 5, 'comment' => '世界観の作り込みが細かく、読み応えがありました。ただ専門用語がやや多く読みにくい箇所もありました。'],
                ['user' => 0, 'rating' => 1, 'comment' => '期待していたほどではありませんでしたが、テーマ自体は興味深く最後まで読めました。'],
            ],
            // 4冊目（2件）
            [
                ['user' => 1, 'rating' => 2, 'comment' => '構成が巧みで、最後の一行まで気が抜けませんでした。今年読んだ中でも上位に入る作品です。'],
                ['user' => 2, 'rating' => 3, 'comment' => '読みやすい文体で、初めてこのジャンルを読む人にもおすすめできる内容でした。'],
            ],
            // 5冊目（3件）
            [
                ['user' => 3, 'rating' => 4, 'comment' => '序盤の展開が少し冗長に感じましたが、後半にかけて面白くなっていきました。'],
                ['user' => 4, 'rating' => 5, 'comment' => '登場人物それぞれの背景がしっかり描かれていて、物語に厚みがありました。'],
                ['user' => 0, 'rating' => 1, 'comment' => '読み終えた後もしばらく余韻が残るような、素晴らしい作品でした。'],
            ],
            // 6冊目（3件）
            [
                ['user' => 1, 'rating' => 2, 'comment' => '全体的にバランスの取れた良作でした。次回作があれば読んでみたいです。'],
                ['user' => 2, 'rating' => 3, 'comment' => '予想を裏切る展開の連続で、最後まで飽きることなく読めました。文句なしの一冊です。'],
                ['user' => 3, 'rating' => 4, 'comment' => '設定は魅力的でしたが、文章のテンポが自分には合いませんでした。'],
            ],
            // 7冊目（3件）
            [
                ['user' => 4, 'rating' => 5, 'comment' => '情景描写が美しく、まるで映像を見ているかのような没入感がありました。'],
                ['user' => 0, 'rating' => 1, 'comment' => 'テーマには共感できましたが、ストーリー展開がやや説明的すぎると感じました。'],
                ['user' => 1, 'rating' => 2, 'comment' => '読みやすく、通勤時間でもさくさく読み進められました。内容も十分満足です。'],
            ],
            // 8冊目（3件）
            [
                ['user' => 2, 'rating' => 3, 'comment' => '登場人物の掛け合いが楽しく、笑いながら読める場面が多かったです。'],
                ['user' => 3, 'rating' => 4, 'comment' => 'テーマの掘り下げ方が深く、読後にいろいろと考えさせられる一冊でした。'],
                ['user' => 4, 'rating' => 5, 'comment' => '面白い部分もありましたが、全体的に既視感のある展開が多かった印象です。'],
            ],
            // 9冊目（4件）
            [
                ['user' => 0, 'rating' => 1, 'comment' => '圧倒的な筆力で一気に引き込まれました。細部までこだわった描写が素晴らしいです。'],
                ['user' => 1, 'rating' => 2, 'comment' => '安定して面白く、シリーズものとしても今後が楽しみになる内容でした。'],
                ['user' => 2, 'rating' => 3, 'comment' => '悪くはないのですが、他の作品と比べるとやや平凡に感じてしまいました。'],
                ['user' => 3, 'rating' => 4, 'comment' => 'キャラクターの成長がしっかり描かれていて、読んでいて気持ちよかったです。'],
            ],
            // 10冊目（4件）
            [
                ['user' => 4, 'rating' => 5, 'comment' => '設定は良かったものの、説明不足に感じる箇所がいくつかありました。'],
                ['user' => 0, 'rating' => 1, 'comment' => '何度も読み返したくなるほど完成度の高い作品でした。強くおすすめします。'],
                ['user' => 1, 'rating' => 2, 'comment' => '文章にリズムがあり、読んでいてとても心地よかったです。満足度の高い一冊でした。'],
                ['user' => 2, 'rating' => 3, 'comment' => '終盤の展開に鳥肌が立ちました。伏線の張り方が本当に見事です。'],
            ],
            // 11冊目（4件）
            [
                ['user' => 3, 'rating' => 4, 'comment' => '程よいボリュームで、休日に一気読みするのにぴったりの作品でした。'],
                ['user' => 4, 'rating' => 5, 'comment' => '期待値が高すぎたせいか、内容自体は普通に感じました。悪くはないです。'],
                ['user' => 0, 'rating' => 1, 'comment' => '文句なしの傑作です。友人にも自信を持っておすすめできます。'],
                ['user' => 1, 'rating' => 2, 'comment' => 'テンポの良い展開と丁寧な描写のバランスが取れていて、読みやすかったです。'],
            ],
        ];
 
        foreach ($reviewsByBook as $bookIndex => $reviews) {
            $book = $books[$bookIndex];
 
            foreach ($reviews as $review) {
                Review::create([
                    'user_id' => $users[$review['user']]->id,
                    'book_id' => $book->id,
                    'rating' => $review['rating'],
                    'comment' => $review['comment'],
                ]);
            }
        }
 
    }
}
