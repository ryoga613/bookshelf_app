<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ReadingPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * 読書計画（reading_plans）ステータス仮定例:
     * 0: 読みたい (not_started)
     * 1: 読書中 (in_progress)
     * 2: 読了 (completed)
     * ※定数やEnumを使っている場合は適宜プロジェクトの定義に合わせてください。
     */
    public function run(): void
    {
        $today = Carbon::today();

        // 1. テストのメインユーザー（山田太郎）を取得または作成
        $mainUser = User::firstOrCreate(
            ['email' => 'yamada@example.com'],
            ['name' => '山田太郎', 'password' => bcrypt('password')]
        );

        // 2. 認可判定用（他人のデータ検証用）の別ユーザーを取得または作成
        $otherUser = User::firstOrCreate(
            ['email' => 'sato@example.com'],
            ['name' => '佐藤花子', 'password' => bcrypt('password')]
        );

        // 3. テスト用の本を必要数確保（なければ作成）
        $books = Book::all();
        if ($books->count() < 10) {
            // 本が足りない場合はダミー作成（必要に応じて件数調整）
            $books = Book::factory()->count(10)->create();
        }

        // -------------------------------------------------------------
        // シナリオ A: 山田太郎（メインユーザー）の読書計画データ集約
        // -------------------------------------------------------------
        
        $plansData = [
            // パターン1: 【未着手】目標日が未来（正常な予定）
            [
                'user_id'     => $mainUser->id,
                'book_id'     => $books[0]->id,
                'status'      => 0, // 読みたい
                'target_date' => $today->copy()->addDays(7), // 7日後
            ],
            // パターン2: 【未着手】目標日が今日（リマインド・期限当日発火用）
            [
                'user_id'     => $mainUser->id,
                'book_id'     => $books[1]->id,
                'status'      => 0, // 読みたい
                'target_date' => $today->copy(), // 今日
            ],
            // パターン3: 【未着手】目標日が過去（期限切れ・遅延フラグ等の発火用）
            [
                'user_id'     => $mainUser->id,
                'book_id'     => $books[2]->id,
                'status'      => 0, // 読みたい
                'target_date' => $today->copy()->subDays(3), // 3日前
            ],
            // パターン4: 【進行中】目標日が未来（順調な進行）
            [
                'user_id'     => $mainUser->id,
                'book_id'     => $books[3]->id,
                'status'      => 1, // 読書中
                'target_date' => $today->copy()->addDays(14), // 14日後
            ],
            // パターン5: 【進行中】目標日が過去（読書中だが期限オーバー）
            [
                'user_id'     => $mainUser->id,
                'book_id'     => $books[4]->id,
                'status'      => 1, // 読書中
                'target_date' => $today->copy()->subDays(1), // 1日前
            ],
            // パターン6: 【完了】目標日より前に完了（達成済み）
            [
                'user_id'     => $mainUser->id,
                'book_id'     => $books[5]->id,
                'status'      => 2, // 読了
                'target_date' => $today->copy()->addDays(5),
            ],
            // パターン7: 【完了】過去に設定して過去に読了（過去履歴用）
            [
                'user_id'     => $mainUser->id,
                'book_id'     => $books[6]->id,
                'status'      => 2, // 読了
                'target_date' => $today->copy()->subDays(10),
            ],

            // -------------------------------------------------------------
            // シナリオ B: 佐藤花子（他ユーザー）のデータ（認可・他人のデータ非表示/変更不可テスト用）
            // -------------------------------------------------------------
            
            // 他人の進行中データ（山田太郎が編集・削除しようとした際に403 Forbiddenになるかテスト）
            [
                'user_id'     => $otherUser->id,
                'book_id'     => $books[7]->id,
                'status'      => 1, // 読書中
                'target_date' => $today->copy()->addDays(3),
            ],
            // 他人の未着手データ
            [
                'user_id'     => $otherUser->id,
                'book_id'     => $books[8]->id,
                'status'      => 0, // 読みたい
                'target_date' => $today->copy()->subDays(2),
            ],
        ];

        // 4. 重複登録を防ぎつつ投入 (firstOrCreate)
        foreach ($plansData as $data) {
            ReadingPlan::firstOrCreate(
                [
                    'user_id' => $data['user_id'],
                    'book_id' => $data['book_id'],
                ],
                [
                    'status'      => $data['status'],
                    'target_date' => $data['target_date'],
                ]
            );
        }
    }
}