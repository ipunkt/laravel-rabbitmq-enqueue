<?php


namespace Ipunkt\RabbitMQ\Test;


use Interop\Amqp\AmqpMessage;
use Ipunkt\RabbitMQ\MessageHandler\MessageHandler;

trait TestsRabbitMQ
{
    public function sendMessage(AmqpMessage $message)
    {
        $messageHelper = $this->app->make(TestHelper::class);
        $messageHelper->send($message);
    }
}