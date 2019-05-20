<?php namespace Ipunkt\RabbitMQ;

use Illuminate\Support\ServiceProvider;
use Interop\Amqp\AmqpConnectionFactory;
use Ipunkt\RabbitMQ\Commands\RabbitMQListenCommand;

/**
 * Class Provider
 * @package App\RabbitMQ
 */
class Provider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/rabbitmq.php' => config_path('rabbitmq.php')
        ]);
    }

    public function register()
    {
        $this->app->bind(AmqpConnectionFactory::class, function() {
            return new \Enqueue\AmqpExt\AmqpConnectionFactory( config('rabbitmq.dsn') );
        });

        $this->app->bind(RabbitMQListenCommand::class);

        $this->commands([
            RabbitMQListenCommand::class
        ]);
    }

}