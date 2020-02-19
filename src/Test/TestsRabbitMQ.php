<?php


namespace Ipunkt\RabbitMQ\Test;


use Interop\Amqp\AmqpMessage;
use Ipunkt\RabbitMQ\MessageHandler\MessageHandler;

trait TestsRabbitMQ
{
    public function sendMessage(AmqpMessage $message)
    {
        /**
         * @var TestHelper $testHelper
         */
        $testHelper = $this->app->make(TestHelper::class);
        $testHelper->send($message);
    }

    /**
     * Create an AmqpMessage to pass to sendMessage
     *
     * @param $routingKey
     * @param array|string $data a string will be passed directly as body, an array will be encoded as json
     */
    protected function makeMessage(string $routingKey, $data) {
        if( is_array($data) )
            $data = json_encode($data);

        $message = new \Interop\Amqp\Impl\AmqpMessage();
        $message->setRoutingKey($routingKey);
        $message->setBody($data);

        return $message;
    }
}