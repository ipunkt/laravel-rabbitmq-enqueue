<?php namespace Ipunkt\RabbitMQ\Events;

use Interop\Amqp\AmqpMessage;

/**
 * Class AmqpMessageSent
 * @package Ipunkt\RabbitMQ\Events
 */
class MessageSent
{
    /**
     * @var AmqpMessage
     */
    private $message;

    /**
     * AmqpMessageSent constructor.
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