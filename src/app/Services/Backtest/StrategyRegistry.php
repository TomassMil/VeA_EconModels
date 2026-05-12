<?php

namespace App\Services\Backtest;

use App\Services\Backtest\Strategies\AltmanZTopN;
use App\Services\Backtest\Strategies\EqualWeightBuyHold;
use App\Services\Backtest\Strategies\GrahamTopN;

/**
 * Visu pieejamo backtest stratēģiju reģistrs.
 *
 * Lai pievienotu jaunu stratēģiju:
 *   1. Implementē App\Services\Backtest\BacktestStrategyInterface
 *   2. Pievieno to šajā reģistrā ($strategies masīvā)
 *   3. Atjauno wizard UI dropdown (automātiski, jo lasa no registry)
 */
class StrategyRegistry
{
    /**
     * @var array<string, class-string<BacktestStrategyInterface>>
     */
    private array $strategies = [
        EqualWeightBuyHold::class,
        GrahamTopN::class,
        AltmanZTopN::class,
    ];

    /**
     * Atgriež visas reģistrētās stratēģijas kā jaunās instances.
     *
     * @return BacktestStrategyInterface[]
     */
    public function all(): array
    {
        return array_map(fn ($class) => app($class), $this->strategies);
    }

    public function get(string $key): ?BacktestStrategyInterface
    {
        foreach ($this->all() as $strategy) {
            if ($strategy->key() === $key) {
                return $strategy;
            }
        }
        return null;
    }
}
