<?php

use App\Support\Money;
use App\Support\RestaurantProfile;

if (! function_exists('money')) {
    function money(int|null $minorUnits, bool $decimals = true): string
    {
        return Money::format($minorUnits, $decimals);
    }
}

if (! function_exists('money_major')) {
    function money_major(float|int $amount, bool $decimals = true): string
    {
        return Money::formatMajor($amount, $decimals);
    }
}

if (! function_exists('restaurant_name')) {
    function restaurant_name(): string
    {
        return RestaurantProfile::get('name');
    }
}

if (! function_exists('app_page_title')) {
    function app_page_title(?string $section = null): string
    {
        $name = restaurant_name();

        return $section ? "{$section} — {$name}" : $name;
    }
}
