<?php namespace Ipunkt\RabbitMQ\Test;

use Interop\Amqp\AmqpMessage;
use Interop\Amqp\AmqpProducer;
use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Queue;
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
        $this->messageHandler
            ->setQueue( $this->mockQueue() )
            ->setContext( $this->mockContext() )
            ->setConsumer( $this->mockConsumer() )
            ->setMessage($message)
            ->handle();
    }

    protected function mockQueue()
    {
        $queue = Mockery::mock(Queue::class);
        $queue->shouldIgnoreMissing($queue);
        return $queue;
    }

    /**
     * @return Context|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    protected function mockContext()
    {
        $context = Mockery::mock(Context::class);
        $context->shouldIgnoreMissing($context);
        $context->shouldReceive('createMessage')->andReturnUsing(function () {
            return new \Interop\Amqp\Impl\AmqpMessage();
        });
        $context->shouldReceive('createProducer')->andReturnUsing(function () {
            return $this->mockProducer();
        });
        return $context;
    }

    protected function mockProducer()
    {
        $producer = Mockery::mock(AmqpProducer::class);
        $producer->shouldIgnoreMissing($producer);
        return $producer;
    }

    /**
     * @return Consumer|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    protected function mockConsumer()
    {
        $consumer = Mockery::mock(Consumer::class);
        $consumer->shouldIgnoreMissing($consumer);
        return $consumer;
    }
}