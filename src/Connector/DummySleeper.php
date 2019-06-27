<?php namespace Ipunkt\RabbitMQ\Connector;

/**
 * Class DummySleeper
 * @package Ipunkt\RabbitMQ\Connector
 */
class DummySleeper implements Sleeper
{

    public function sleep()
    {
        $this->doNothing();
    }

    private function doNothing()
    {
    }
}