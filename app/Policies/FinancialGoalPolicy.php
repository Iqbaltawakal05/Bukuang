<?php

namespace App\Policies;

use App\Models\FinancialGoal;
use App\Models\User;

class FinancialGoalPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, FinancialGoal $financialGoal): bool
    {
        return $user->id === $financialGoal->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, FinancialGoal $financialGoal): bool
    {
        return $user->id === $financialGoal->user_id;
    }

    public function delete(User $user, FinancialGoal $financialGoal): bool
    {
        return $user->id === $financialGoal->user_id;
    }
}
