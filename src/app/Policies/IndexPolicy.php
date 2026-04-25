<?php

namespace App\Policies;

use App\Models\Index;
use App\Models\User;

class IndexPolicy
{
    public function view(User $user, Index $index): bool
    {
        return $index->is_public || $user->id === $index->user_id;
    }

    public function update(User $user, Index $index): bool
    {
        return $user->id === $index->user_id;
    }

    public function delete(User $user, Index $index): bool
    {
        return $user->id === $index->user_id;
    }
}
