<?php namespace Ipunkt\RabbitMQ\Contracts;

/**
 * Interface TakesMessageHandler
 * @package Ipunkt\RabbitMQ\Contracts
 */
interface TakesMessageHandler
{
    /**
     * @param string $routingKey
     * @param string $class
     */
    function registerHandler(string $routingKey, string $class);

}