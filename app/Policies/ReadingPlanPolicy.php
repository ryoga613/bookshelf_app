<?php

namespace App\Policies;

use App\Models\ReadingPlan;
use App\Models\User;

class ReadingPlanPolicy
{
    /**
     * 読書計画の操作はすべて所有者のみ許可する
     * （ポリシーのメソッドは「ログインユーザー → 対象モデル」の順で引数を受け取る）
     */
    public function view(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->id === $readingPlan->user_id;
    }

    public function complete(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->id === $readingPlan->user_id;
    }

    public function update(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->id === $readingPlan->user_id;
    }

    public function delete(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->id === $readingPlan->user_id;
    }
}
