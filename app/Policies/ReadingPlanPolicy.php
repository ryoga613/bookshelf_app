<?php

namespace App\Policies;

use App\Models\ReadingPlan;
use App\Models\User;

class ReadingPlanPolicy
{
    /**
     * Create a new policy instance.
     */
    public function index(ReadingPlan $readingPlan, User $user)
    {
        return true;
    }

    public function create(ReadingPlan $readingPlan, User $user)
    {
        return $readingPlan->user_id === $user->id;
    }

    public function store(ReadingPlan $readingPlan, User $user)
    {
        return $readingPlan->user_id === $user->id;
    }

    public function complete(ReadingPlan $readingPlan, User $user)
    {
        return $readingPlan->user_id === $user->id;
    }

    public function edit(ReadingPlan $readingPlan, User $user)
    {
        return $readingPlan->user_id === $user->id;
    }

    public function update(ReadingPlan $readingPlan, User $user)
    {
        return $readingPlan->user_id === $user->id;
    }

    public function destroy(ReadingPlan $readingPlan, User $user)
    {
        return $readingPlan->user_id === $user->id;
    }
}
