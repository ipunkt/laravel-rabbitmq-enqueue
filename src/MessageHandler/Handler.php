<?php namespace Ipunkt\RabbitMQ\MessageHandler;

use Interop\Queue\Message;
use Interop\Queue\Processor;

/**
 * Interface Handler
 * @package Ipunkt\RabbitMQ
 */
interface Handler
{

    /**
     * @param Message $message
     * @return Processor::ACK|Processor::REJECT|Processor::REQUEUE
     */
    function handle(Message $message);

}