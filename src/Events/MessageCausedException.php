<?php namespace Ipunkt\RabbitMQ\Events;

use Interop\Amqp\AmqpMessage;
use Throwable;

/**
 * Class MessageCausedException
 * @package Ipunkt\RabbitMQ\Events
 */
class MessageCausedException
{
    /**
     * @var AmqpMessage
     */
    private $message;
    /**
     * @var Throwable
     */
    private $throwable;

    /**
     * MessageCausedException constructor.
     * @param AmqpMessage $message
     * @param Throwable $throwable
     */
    public function __construct(AmqpMessage $message, Throwable $throwable) {
        $this->message = $message;
        $this->throwable = $throwable;
    }

    /**
     * @return AmqpMessage
     */
    public function getMessage(): AmqpMessage
    {
        return $this->message;
    }

    /**
     * @return Throwable
     */
    public function getThrowable(): Throwable
    {
        return $this->throwable;
    }

}