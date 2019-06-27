<?php namespace Ipunkt\RabbitMQ\Rpc\Exceptions;

use Throwable;

/**
 * Class QueueNotCreatedException
 * @package Ipunkt\RabbitMQ\Rpc\Exceptions
 */
class QueueNotCreatedException extends RpcException
{

    public function __construct($action, int $code = 0, Throwable $throwable = null)
    {
        parent::__construct("Attempted to use $action on rpc without creating its queue", $code, $throwable);
    }

}