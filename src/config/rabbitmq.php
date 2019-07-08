<?php
return [
    'rpc' => [
        'timeout' => env('RMQ_RPC_TIMEOUT',10000),
    ],

    'dsn' => 'amqp+ext://'.env('RMQ_USERNAME', 'guest').':'.env('RMQ_PASSWORD', 'guest').'@'.
        env('RMQ_HOST', 'rabbitmq').':'.env('RMQ_PORT', 5672).'/'.urlencode(env('RMQ_VHOST', '/'))
        .'?heartbeat=30'
];