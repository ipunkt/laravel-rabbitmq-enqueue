<?php
return [
    'dsn' => 'amqp+ext://'.env('RMQ_USERNAME').':'.env('RMQ_PASSWORD').'@'.
        env('RMQ_HOST', 'rabbitmq').':'.env('RMQ_PORT', 5672).'/'.urlencode(env('RMQ_VHOST', '/'))
];