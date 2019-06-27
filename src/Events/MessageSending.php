<?php namespace Ipunkt\RabbitMQ\Events;

use Interop\Amqp\AmqpMessage;

/**
 * Class MessageSending
 * @package Ipunkt\RabbitMQ\Events
 */
class MessageSending
{
    /**
     * @var AmqpMessage
     */
    private $message;

    /**
     * MessageSending constructor.
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