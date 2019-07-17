<?php namespace Ipunkt\RabbitMQ\Providers;

use function foo\func;
use Illuminate\Support\ServiceProvider;
use Ipunkt\RabbitMQ\Commands\RabbitMQListenCommand;
use Ipunkt\RabbitMQ\Sender\RabbitMQ;

/**
 * Class RabbitMQBaseProvider
 * @package Ipunkt\RabbitMQ\Providers
 */
class RabbitMQBaseProvider extends ServiceProvider
{

    /**
     * @var null
     */
    protected $listenQueue = null;

    /**
     * @var null
     */
    protected $listenQueueConfig = 'rabbitmq.queue.name';

    protected $handlers = [];

    protected function bindings() {
        return [
        ];
    }

    protected function mapQueuesOnSend() {
        return [
        ];
    }

    protected function mapExchangesOnSend() {
        return [
        ];
    }

    public function register()
    {
        $this->registerQueue();

        $this->registerBindings();

        $this->registerHandlers();

        $this->registerQueueRename();

        $this->registerExchangeRename();
    }

    private function registerBindings()
    {
        $this->app->resolving(RabbitMQListenCommand::class, function (RabbitMQListenCommand $command) {
            foreach ($this->bindings() as $binding) {
                list($exchange, $routingKey) = $binding;

                $command->addBinding($exchange, $routingKey);
            }
        });
    }

    private function registerHandlers()
    {
        $this->app->resolving(RabbitMQListenCommand::class, function (RabbitMQListenCommand $command) {
            foreach ($this->handlers as $binding) {
                list($routingKey, $handler) = $binding;

                $command->registerHandler($routingKey, $handler);
            }
        });
    }

    private function registerQueue()
    {
        $this->app->resolving(RabbitMQListenCommand::class, function (RabbitMQListenCommand $command) {
            if( is_string($this->listenQueue) ) {
                $command->setQueue( $this->listenQueue );
                return;
            }

            $command->setQueue( config($this->listenQueueConfig) );
        });
    }

    private function registerQueueRename()
    {
        $this->app->resolving(RabbitMQ::class, function (RabbitMQ $sender) {
            $sender->setQueueRename(function($queue) {
                $queueNameMap = $this->mapQueuesOnSend();
                if(array_key_exists($queue, $queueNameMap))
                    return $queueNameMap[$queue];

                return $queue;
            });
        });
    }

    private function registerExchangeRename()
    {
        $this->app->resolving(RabbitMQ::class, function (RabbitMQ $sender) {
            $sender->setExchangeRename(function($exchange) {
                $exchangeNameMap = $this->mapExchangesOnSend();
                if(array_key_exists($exchange, $exchangeNameMap))
                    return $exchangeNameMap[$exchange];

                return $exchange;
            });
        });
    }

}