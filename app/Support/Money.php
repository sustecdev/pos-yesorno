<?php

namespace App\Support;

class Money
{
    public static function symbol(): string
    {
        return config('teboos.currency_symbol', 'K');
    }

    public static function format(int|null $minorUnits, bool $decimals = true): string
    {
        $amount = ($minorUnits ?? 0) / 100;
        $formatted = number_format(
            abs($amount),
            $decimals ? 2 : 0,
            '.',
            ',',
        );

        $prefix = $amount < 0 ? '-' : '';
        $symbol = self::symbol();

        return "{$prefix}{$symbol} {$formatted}";
    }

    public static function formatMajor(float|int $amount, bool $decimals = true): string
    {
        return self::format((int) round($amount * 100), $decimals);
    }
}
