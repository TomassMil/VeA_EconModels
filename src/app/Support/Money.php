<?php

namespace App\Support;

class Money
{
    public const SCALE_CASH = 2;
    public const SCALE_SHARES = 6;

    public static function add(string|float|int $a, string|float|int $b, int $scale = self::SCALE_CASH): string
    {
        return bcadd((string) $a, (string) $b, $scale);
    }

    public static function sub(string|float|int $a, string|float|int $b, int $scale = self::SCALE_CASH): string
    {
        return bcsub((string) $a, (string) $b, $scale);
    }

    public static function mul(string|float|int $a, string|float|int $b, int $scale = self::SCALE_CASH): string
    {
        return bcmul((string) $a, (string) $b, $scale);
    }

    public static function div(string|float|int $a, string|float|int $b, int $scale = self::SCALE_SHARES): string
    {
        if (bccomp((string) $b, '0', self::SCALE_SHARES) === 0) {
            throw new \DivisionByZeroError('Money::div divisor is zero');
        }
        return bcdiv((string) $a, (string) $b, $scale);
    }

    public static function gt(string|float|int $a, string|float|int $b, int $scale = self::SCALE_CASH): bool
    {
        return bccomp((string) $a, (string) $b, $scale) === 1;
    }

    public static function gte(string|float|int $a, string|float|int $b, int $scale = self::SCALE_CASH): bool
    {
        return bccomp((string) $a, (string) $b, $scale) >= 0;
    }

    public static function lte(string|float|int $a, string|float|int $b, int $scale = self::SCALE_CASH): bool
    {
        return bccomp((string) $a, (string) $b, $scale) <= 0;
    }

    public static function eq(string|float|int $a, string|float|int $b, int $scale = self::SCALE_CASH): bool
    {
        return bccomp((string) $a, (string) $b, $scale) === 0;
    }

    public static function neg(string|float|int $a, int $scale = self::SCALE_CASH): string
    {
        return bcsub('0', (string) $a, $scale);
    }
}
