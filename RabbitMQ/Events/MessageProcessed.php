<?php namespace Ipunkt\RabbitMQ\Events;

use Interop\Amqp\AmqpMessage;

/**
 * Class AmqpMessageProcessed
 * @package Ipunkt\RabbitMQ\Events
 */
class MessageProcessed
{
    /**
     * @var AmqpMessage
     */
    private $message;
    private $result;

    /**
     * AmqpMessageProcessed constructor.
     * @param AmqpMessage $message
     * @param $result
     */
    public function __construct(AmqpMessage $message, $result) {
        $this->message = $message;
        $this->result = $result;
    }

    /**
     * @return AmqpMessage
     */
    public function getMessage(): AmqpMessage
    {
        return $this->message;
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

}