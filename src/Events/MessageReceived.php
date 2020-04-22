<?php namespace Ipunkt\RabbitMQ\Events;

use Interop\Amqp\AmqpMessage;

/**
 * Class AmqpMessageReceived
 * @package Ipunkt\RabbitMQ\Events
 */
class MessageReceived
{
    /**
     * @var AmqpMessage
     */
    private $message;

    /**
     * AmqpMessageReceived constructor.
     * @param AmqpMessage $message
     */
    public function __construct(AmqpMessage $message) {
        $this->message = $message;
    }

    /**
     * @return AmqpMessage
     */
    public function getMessage(): AmqpMessage
    {
        return $this->message;
    }

}