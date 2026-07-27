<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /**
     * Create a new policy instance.
     */
    public function store(): bool
    {
        return true;

    }

    public function show()
    {
        //
    }

    public function edit()
    {
        //
    }

    public function update(User $user, Review $review)
    {
        return $review->user_id === $user->id;
    }

    public function delete(User $user, Review $review)
    {
        return $review->user_id === $user->id;
    }
}
