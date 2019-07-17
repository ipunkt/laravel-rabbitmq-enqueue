<?php namespace App\Providers;

use Ipunkt\RabbitMQ\Providers\RabbitMQBaseProvider;

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

    /*
     * $rabbitMQ->publish([])->onQueue('default')
     * Will send to `actual-queue` with the example configuration bellow
     */
    protected function mapQueuesOnSend() {
        return [
            // 'default' => 'actual-queue'
        ];
    }

    /*
     * $rabbitMQ->publish([])->onExchange('default', 'routing.key')
     * Will send to `actual-exchange` with the example configuration bellow
     */
    protected function mapExchangesOnSend() {
        return [
            // 'default' => 'actual-exchange'
        ];
    }

}