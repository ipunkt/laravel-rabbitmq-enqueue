<?php namespace Ipunkt\RabbitMQ\Providers;

/**
 * Class RabbitMQProviderRabbitMQ
 * @package Ipunkt\RabbitMQ\Providers
 */
class RabbitMQProvider extends RabbitMQBaseProvider
{

    /**
     * Fixed name of the queue rabbitmq:listen will create, bind and listen to.
     * takes precedence over $listenQueueConfig
     */
    //protected $listenQueue = 'queue';

    /**
     * rabbitmq:listen will create, bind and listen to the queue named by config($listenQueueConfig)
     * This setting is overridden if $listenQueue is set
     *
     * @var string
     */
    protected $listenQueueConfig = 'rabbitmq.queue.name';

    protected function bindings()
    {
        return [
            // [ 'exchange', 'routing-key' ]
        ];
    }


    protected $handlers = [
        // [ 'routing-key', Handler::class ]
    ];

}