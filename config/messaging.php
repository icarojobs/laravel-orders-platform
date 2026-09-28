<?php

return [

    /*
    | Where order domain events are published: "rabbitmq" or "log".
    */
    'driver' => env('MESSAGING_DRIVER', 'log'),

    'rabbitmq' => [
        'host' => env('RABBITMQ_HOST', '127.0.0.1'),
        'port' => (int) env('RABBITMQ_PORT', 5672),
        'user' => env('RABBITMQ_USER', 'guest'),
        'password' => env('RABBITMQ_PASSWORD', 'guest'),
        'vhost' => env('RABBITMQ_VHOST', '/'),
        'exchange' => env('RABBITMQ_EXCHANGE', 'orders.events'),
    ],

];
