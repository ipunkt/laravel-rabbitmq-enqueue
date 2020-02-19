<?php namespace Ipunkt\RabbitMQ\Test;

use Interop\Amqp\AmqpMessage;
use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Ipunkt\RabbitMQ\Contracts\TakesMessageHandler;
use Ipunkt\RabbitMQ\MessageHandler\MessageHandler;
use Mockery;

/**
 * Class TestHelper
 * @package Ipunkt\RabbitMQ\Test
 */
class TestHelper implements TakesMessageHandler
{
    /**
     * @var MessageHandler
     */
    private $messageHandler;

    /**
     * TestHelper constructor.
     * @param MessageHandler $messageHandler
     */
    public function __construct(MessageHandler $messageHandler) {
        $this->messageHandler = $messageHandler;
    }

    /**
     * @param string $routingKey
     * @param string $class
     */
    public function registerHandler(string $routingKey, string $class)
    {
        $this->messageHandler->registerHandler($routingKey, $class);
    }

    public function send(AmqpMessage $message)
    {
        $context = Mockery::mock(Context::class);
        $context->shouldIgnoreMissing($context);
        $consumer = Mockery::mock(Consumer::class);
        $consumer->shouldIgnoreMissing($consumer);
        $this->messageHandler
            ->setContext($context)
            ->setConsumer($consumer)
            ->setMessage($message)
            ->handle();
    }
}