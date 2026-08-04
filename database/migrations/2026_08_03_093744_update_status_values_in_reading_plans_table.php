<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 既存データを上から順にズレないように更新する
        // (※ 0->1, 1->2, 2->3 に書き換え)
        DB::table('reading_plans')->where('status', 2)->update(['status' => 3]);
        DB::table('reading_plans')->where('status', 1)->update(['status' => 2]);
        DB::table('reading_plans')->where('status', 0)->update(['status' => 1]);
    }

    public function down(): void
    {
        // 元に戻す処理 (ロールバック用)
        DB::table('reading_plans')->where('status', 1)->update(['status' => 0]);
        DB::table('reading_plans')->where('status', 2)->update(['status' => 1]);
        DB::table('reading_plans')->where('status', 3)->update(['status' => 2]);
    }
};
