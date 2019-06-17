<?php namespace Ipunkt\RabbitMQ;

use Illuminate\Support\ServiceProvider;
use Interop\Amqp\AmqpConnectionFactory;
use Ipunkt\RabbitMQ\Commands\RabbitMQListenCommand;
use Ipunkt\RabbitMQ\Connector\SecondSleeper;
use Ipunkt\RabbitMQ\Connector\Sleeper;

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
        $this->registerAmqpConnection();

        $this->registerDefaultSleeper();

        $this->registerCommand();
    }

    private function registerAmqpConnection()
    {
        $this->app->bind(AmqpConnectionFactory::class, function() {
            return new \Enqueue\AmqpExt\AmqpConnectionFactory( config('rabbitmq.dsn') );
        });
    }

    private function registerDefaultSleeper()
    {
        $this->app->register(Sleeper::class, function () {
            return new SecondSleeper(5);
        });
    }

    private function registerCommand()
    {
        $this->commands([
            RabbitMQListenCommand::class
        ]);
    }

}