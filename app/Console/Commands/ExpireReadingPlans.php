<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use Illuminate\Console\Command;

class ExpireReadingPlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:expire-reading-plans';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '期日を過ぎた進行中の読書計画を一括で期限切れにする';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // 進行中（未読・読書中）で、期日が今日より前の計画を一括更新する
        $count = ReadingPlan::whereIn('status', ReadingPlanStatus::inProgressValues())
            ->whereDate('target_date', '<', today())
            ->update(['status' => ReadingPlanStatus::Expired->value]);

        $this->info("{$count}件の読書計画を期限切れにしました");

        return self::SUCCESS;
    }
}
