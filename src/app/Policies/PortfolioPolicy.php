<?php

namespace App\Policies;

use App\Models\Portfolio;
use App\Models\User;

class PortfolioPolicy
{
    public function view(User $user, Portfolio $portfolio): bool
    {
        // System portfolios (model backtests) are visible to all authenticated users
        return $portfolio->is_system || $user->id === $portfolio->user_id;
    }

    public function update(User $user, Portfolio $portfolio): bool
    {
        // Sistēmas portfeļus rediģēt nedrīkst neviens — tie tiek pārģenerēti caur backtest wizard
        return ! $portfolio->is_system && $user->id === $portfolio->user_id;
    }

    public function delete(User $user, Portfolio $portfolio): bool
    {
        return ! $portfolio->is_system && $user->id === $portfolio->user_id;
    }
}
