<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Service Charge Brackets
    |--------------------------------------------------------------------------
    |
    | The fee charged per transaction, by amount (Philippine Peso). Evaluated
    | top to bottom: the first bracket whose "up_to" is >= the amount wins.
    | The final bracket must have "up_to" => null (the catch-all).
    |
    | THIS IS THE ONLY PLACE THESE RATES ARE DEFINED. The server computes the
    | charge from this table, and the dashboard modal renders its rate guide
    | and its live preview from this same table. Edit here, nowhere else.
    |
    */
    'service_charge_brackets' => [
        ['up_to' => 500,    'fee' => 10],
        ['up_to' => 1000,   'fee' => 15],
        ['up_to' => 2500,   'fee' => 20],
        ['up_to' => 5000,   'fee' => 25],
        ['up_to' => 10000,  'fee' => 30],
        ['up_to' => 20000,  'fee' => 50],
        ['up_to' => null,   'fee' => 100],
    ],

    /*
    |--------------------------------------------------------------------------
    | Quick Amounts
    |--------------------------------------------------------------------------
    |
    | One-tap buttons above the Amount field in the transaction modal. Most
    | transactions at the counter are round numbers, and every second saved
    | while a customer waits is worth having. Adjust to whatever this shop
    | actually sees most often.
    |
    */
    'quick_amounts' => [100, 200, 500, 1000, 2000, 5000],

    /*
    |--------------------------------------------------------------------------
    | End Day Behaviour
    |--------------------------------------------------------------------------
    |
    | Which balances are zeroed when the day is closed. Physical cash is taken
    | out of the drawer at closing time, so it resets; the GCash float stays in
    | the wallet overnight and carries over to the next day's opening balance.
    |
    */
    'end_day_resets' => [
        'cash' => true,
        'gcash' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-close Cutoff
    |--------------------------------------------------------------------------
    |
    | How many hours a session may stay open before the scheduler closes it for
    | the operator. Set this comfortably longer than the shop's longest real
    | trading day — closing a day that is still running is far worse than
    | leaving a forgotten one open an extra hour.
    |
    | Requires `php artisan schedule:run` to be invoked every minute. See the
    | README for the Windows Task Scheduler setup.
    |
    */
    'auto_close_after_hours' => 16,

];
