<?php

return [
    'name' => env('TEBOOS_RESTAURANT_NAME', 'TeboOS Restaurant'),
    'tax_rate' => (float) env('TEBOOS_TAX_RATE', 0.16),
    'currency_code' => env('TEBOOS_CURRENCY_CODE', 'ZMW'),
    'currency_symbol' => env('TEBOOS_CURRENCY_SYMBOL', 'K'),
    'currency_name' => env('TEBOOS_CURRENCY_NAME', 'Kwacha'),
];
