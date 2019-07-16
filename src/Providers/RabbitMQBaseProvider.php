<?php namespace Ipunkt\RabbitMQ\Providers;

use function foo\func;
use Illuminate\Support\ServiceProvider;
use Ipunkt\RabbitMQ\Commands\RabbitMQListenCommand;

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

    protected $bindings = [];

    protected $handlers = [];

    public function register()
    {
        $this->registerQueue();

        $this->registerBindings();

        $this->registerHandlers();
    }

    private function registerBindings()
    {
        $this->app->resolving(RabbitMQListenCommand::class, function (RabbitMQListenCommand $command) {
            foreach ($this->bindings as $binding) {
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
            if($this->listenQueue === null) {
                $command->setQueue( config($this->listenQueueConfig) );
                return;
            }
        });
    }

}