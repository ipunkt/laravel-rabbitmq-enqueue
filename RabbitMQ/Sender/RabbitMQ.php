<?php namespace Ipunkt\RabbitMQ\Sender;

use AMQPChannelException;
use Closure;
use Enqueue\AmqpExt\AmqpContext;
use Interop\Amqp\AmqpConnectionFactory;
use Interop\Amqp\Impl\AmqpMessage;
use Interop\Queue\Context;
use Interop\Queue\Destination;
use Interop\Queue\Message;
use Ipunkt\RabbitMQ\Events\MessageSending;
use Ipunkt\RabbitMQ\Events\MessageSent;

/**
 * Class RabbitMQ
 * @package Ipunkt\RabbitMQ\Sender
 */
class RabbitMQ
{

    /**
     * @var array
     */
    protected $topics = [];

    /**
     * @var Closure
     */
    protected $renameExchange;

    /**
     * @var Closure
     */
    protected $renameQueue;

    /**
     * @var array
     */
    protected $queues = [];

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var AmqpConnectionFactory
     */
    protected $connectionFactory;

    /**
     * @var Context
     */
    protected $context;

    /**
     * RabbitMQ constructor.
     * @param AmqpConnectionFactory $connectionFactory
     */
    public function __construct(AmqpConnectionFactory $connectionFactory) {
        $this->connectionFactory = $connectionFactory;

        $this->dontRenameQueues();
        $this->dontRenameExchanges();
    }

    public function publish(array $data)
    {
        $this->data = $data;
        return $this;
    }

    public function onExchange($exchange, $routingKey)
    {
        $this->connect();


        $message = new AmqpMessage();
        $message->setRoutingKey($routingKey);

	    try {
		    $this->send($exchange, $message);
	    } catch(AMQPChannelException $e) {
		    $this->reconnect();

		    $exchange = $this->buildExchange($exchange);

		    $this->send($exchange, $message);
	    }
    }

    public function onQueue($queue)
    {
        $this->connect();

        $queue = $this->buildQueue($queue);

	    try {
		    $this->send($queue);
	    } catch(AMQPChannelException $e) {
		    $this->reconnect();

		    $queue = $this->buildQueue($queue);

		    $this->send($queue);
	    }
    }

    private function send( Destination $to, Message $message = null ) {
        if( !$message instanceof AmqpMessage )
            $message = new AmqpMessage();

        $message->setBody( json_encode($this->data) );
        $message->setContentType('application/json');

        $producer = $this->context->createProducer();

        event(new MessageSending($message));
		$producer->send($to, $message);
        event(new MessageSent($message));
    }

	protected function reconnect() {
    	$this->disconnect();
    	$this->connect();
	}


	protected function disconnect() {
    	if($this->context instanceof AmqpContext)
    		$this->context->close();

    	$this->context = null;
    	$this->clearExchanges();
	}

	protected function clearExchanges() {
		$this->topics = [];
	}

    protected function connect() {
        if( $this->context instanceof Context )
            return;

        $this->context = $this->connectionFactory->createContext();
    }

    private function buildExchange($exchange)
    {
        if( !$this->exchangeExists($exchange) ) {
            $exchangeRenamer = $this->renameExchange;
            $renamedExchange = $exchangeRenamer($exchange);
            $topic = $this->context->createTopic($renamedExchange);
            $this->topics[$exchange] = $topic;
        }

        return $this->topics[$exchange];
    }

    protected function exchangeExists($exchangeName) {
        return array_key_exists($exchangeName, $this->topics);
    }

    private function buildQueue($queueName)
    {
        if( !$this->queueExists($queueName)) {
            $queueRenamer = $this->renameQueue;
            $renamedQueue = $queueRenamer($queueName);
            $queue = $this->context->createQueue($renamedQueue);
            $this->queues[$queueName] = $queue;
        }

        return $this->queues[$queueName];
    }

    private function queueExists($queue)
    {
        return array_key_exists($queue, $this->queues);
    }

    public function setQueueRename(Closure $renamer)
    {
        $this->renameQueue = $renamer;
    }

    public function setExchangeRename(Closure $renamer)
    {
        $this->renameExchange = $renamer;
    }

    public function dontRenameQueues(): void
    {
        $this->renameQueue = function ($queue) {
            return $queue;
        };
    }

    public function dontRenameExchanges(): void
    {
        $this->renameExchange = function ($exchange) {
            return $exchange;
        };
    }

}