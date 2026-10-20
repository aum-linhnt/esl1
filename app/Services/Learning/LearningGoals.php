<?php

namespace App\Services\Learning;

use App\Models\LearningGoal;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

final class LearningGoals
{
    public function ready(): bool
    {
        return Schema::hasTable('learner_learning_goals');
    }

    public function forUser(User $user): ?LearningGoal
    {
        if (! $this->ready()) return null;
        return LearningGoal::where('user_id', $user->id)->first();
    }
}
