<?php namespace Ipunkt\RabbitMQ\Connector;

use AMQPConnectionException;
use Closure;
use Enqueue\AmqpExt\AmqpContext;
use Illuminate\Queue\MaxAttemptsExceededException;
use Interop\Queue\Context;

/**
 * Class Connector
 */
class Connector
{

    /**
     * @var AmqpContext|Context
     */
    private $context;

    /**
     * @var bool
     */
    private $connected;

    /**
     * @var int
     */
    private $connectionAttempts;

    /**
     * @var int
     */
    private $maxConnectionAttempts = 5;

    /**
     * @var Closure
     */
    private $connectCallback;
    /**
     * @var Sleeper
     */
    private $sleeper;

    /**
     * Connector constructor.
     * @param Sleeper $sleeper
     */
    public function __construct(Sleeper $sleeper) {
        $this->sleeper = $sleeper;
    }

    public function connect()
    {
        $this->resetConnectionVariables();

        $this->loopUntilConnected();

    }
    private function resetConnectionVariables()
    {
        $this->connectionAttempts = 0;
        $this->connected = false;
    }

    private function loopUntilConnected()
    {
        do {
            try {
                $this->attemptConnection();

                $this->connected = true;
            } catch(AMQPConnectionException $e) {
                $this->incrementConnectionAttempts();
                $this->assertConnectionAttemptsLeft();

                $this->sleeper->sleep();
            }
        } while( !$this->connected );
    }

    private function attemptConnection()
    {
        $connectCallback = $this->connectCallback;

        $connectCallback( $this->context );
    }

    private function incrementConnectionAttempts()
    {
        $this->connectionAttempts += 1;
    }

    private function assertConnectionAttemptsLeft()
    {
        $maxAttemptsReached = $this->maxConnectionAttempts <= $this->connectionAttempts;
        if( $maxAttemptsReached )
            throw new MaxAttemptsExceededException();
    }

    /**
     * @param AmqpContext|Context $context
     * @return Connector
     */
    public function setContext($context)
    {
        $this->context = $context;
        return $this;
    }

    /**
     * @param Sleeper $sleeper
     * @return Connector
     */
    public function setSleeper(Sleeper $sleeper): Connector
    {
        $this->sleeper = $sleeper;
        return $this;
    }

    /**
     * @param Closure $connectCallback
     * @return Connector
     */
    public function setConnectCallback(Closure $connectCallback): Connector
    {
        $this->connectCallback = $connectCallback;
        return $this;
    }

    public function setMaximumAttempts($maxConnectionAttempts)
    {
        $this->maxConnectionAttempts = $maxConnectionAttempts;
    }
}