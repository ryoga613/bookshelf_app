<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\Notification;
use App\Models\ReadingPlan;
use Illuminate\Console\Command;

class SendGoalReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-goal-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '読書計画の目標日に合わせてリマインド通知を作成する';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = today();

        // timing => [対象の目標日, 通知タイトル]
        $timings = [
            'three_days_before' => [$today->copy()->addDays(3), '目標日まであと3日です'],
            'on_due_date' => [$today->copy(), '今日が目標日です'],
            'three_days_after' => [$today->copy()->subDays(3), '目標日を3日過ぎています'],
        ];

        $count = 0;

        foreach ($timings as $timing => [$date, $title]) {
            ReadingPlan::with('book')
                ->where('status', '!=', ReadingPlanStatus::Completed)
                ->whereDate('target_date', $date)
                ->chunkById(100, function ($plans) use ($timing, $title, &$count) {
                    foreach ($plans as $plan) {
                        Notification::create([
                            'type' => 'goal_reminder',
                            'user_id' => $plan->user_id,
                            'data' => [
                                'timing' => $timing,
                                'title' => $title,
                                'body' => "「{$plan->book->title}」の読書目標日は {$plan->target_date->format('Y/m/d')} です。",
                                'plan_id' => $plan->id,
                            ],
                        ]);
                        $count++;
                    }
                });
        }

        $this->info("{$count}件のリマインドを作成しました");

        return self::SUCCESS;
    }
}
