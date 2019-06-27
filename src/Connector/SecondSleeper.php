<?php namespace Ipunkt\RabbitMQ\Connector;

/**
 * Class SecondSleeper
 * @package Ipunkt\RabbitMQ\Connector
 */
class SecondSleeper implements Sleeper
{
    private $secondToSleep;

    /**
     * SecondSleeper constructor.
     * @param $secondToSleep
     */
    public function __construct($secondToSleep) {
        $this->secondToSleep = $secondToSleep;
    }

    public function sleep()
    {
        sleep($this->secondToSleep);
    }
}