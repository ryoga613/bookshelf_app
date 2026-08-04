<?php

namespace Database\Seeders;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ReadingPlanSeeder extends Seeder
{
    public function run(): void
    {
        $User1 = User::firstOrCreate(
            ['email' => 'yamada@example.com'],
            ['name' => '山田太郎', 'password' => bcrypt('password')]
        );

        $User2 = User::firstOrCreate(
            ['email' => 'suzuki@example.com'],
            ['name' => '鈴木花子', 'password' => bcrypt('password')],
        );

        $books = Book::all();
        if ($books->count() < 10) {
            $books = Book::factory()->count(10 - $books->count())->create();
            $books = Book::all();
        }

        $plansData = [
            [
                'user_id' => $User1->id,
                'book_id' => $books[0]->id,
                'status' => ReadingPlanStatus::NotCompleted,
                'target_date' => Carbon::today()->copy()->addDays(7),
            ],
            [
                'user_id' => $User1->id,
                'book_id' => $books[1]->id,
                'status' => ReadingPlanStatus::NotCompleted,
                'target_date' => Carbon::today()->copy(),
            ],
            [
                'user_id' => $User1->id,
                'book_id' => $books[2]->id,
                'status' => ReadingPlanStatus::NotCompleted,
                'target_date' => Carbon::today()->copy()->subDays(3),
            ],

            [
                'user_id' => $User1->id,
                'book_id' => $books[3]->id,
                'status' => ReadingPlanStatus::NotCompleted,
                'target_date' => Carbon::today()->copy()->addDays(14),
            ],
            [
                'user_id' => $User1->id,
                'book_id' => $books[4]->id,
                'status' => ReadingPlanStatus::NotCompleted,
                'target_date' => Carbon::today()->copy()->subDays(1),
            ],

            [
                'user_id' => $User1->id,
                'book_id' => $books[5]->id,
                'status' => ReadingPlanStatus::Completed,
                'target_date' => Carbon::today()->copy()->addDays(5),
                'completed_at' => Carbon::today()->copy()->subDays(1),
            ],
            [
                'user_id' => $User1->id,
                'book_id' => $books[6]->id,
                'status' => ReadingPlanStatus::Completed,
                'target_date' => Carbon::today()->copy()->subDays(10),
                'completed_at' => Carbon::today()->copy()->subDays(5),
            ],

            [
                'user_id' => $User2->id,
                'book_id' => $books[7]->id,
                'status' => ReadingPlanStatus::NotCompleted,
                'target_date' => Carbon::today()->copy()->addDays(3),
            ],
            [
                'user_id' => $User2->id,
                'book_id' => $books[8]->id,
                'status' => ReadingPlanStatus::NotCompleted,
                'target_date' => Carbon::today()->copy()->subDays(2),
            ],
            [
                'user_id' => $User2->id,
                'book_id' => $books[9]->id,
                'status' => ReadingPlanStatus::Completed,
                'target_date' => Carbon::today()->copy()->subDays(7),
                'completed_at' => Carbon::today()->copy()->subDays(3),
            ],
        ];

        foreach ($plansData as $data) {
            ReadingPlan::firstOrCreate(
                [
                    'user_id' => $data['user_id'],
                    'book_id' => $data['book_id'],
                ],
                [
                    'status' => $data['status'],
                    'target_date' => $data['target_date'],
                    'completed_at' => $data['completed_at'] ?? null,
                ]
            );
        }
    }
}
