<?php namespace Ipunkt\RabbitMQ;

use Illuminate\Support\ServiceProvider;
use Interop\Amqp\AmqpConnectionFactory;
use Interop\Amqp\AmqpMessage;
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
            __DIR__.'/config/rabbitmq.php' => config_path('rabbitmq.php'),
            __DIR__.'/Providers/RabbitMQProvider.php' => app_path('Providers/RabbitMQProviderRabbitMQ.php'),
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
        $this->app->bind(Sleeper::class, function () {
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