<?php namespace Ipunkt\RabbitMQ\Sender;

use Closure;
use Illuminate\Support\Facades\Log;
use Interop\Amqp\AmqpConnectionFactory;
use Interop\Amqp\Impl\AmqpMessage;
use Interop\Queue\Context;
use Interop\Queue\Destination;
use Interop\Queue\Message;
use Ipunkt\RabbitMQ\Events\MessageSending;
use Ipunkt\RabbitMQ\Events\MessageSent;
use Ipunkt\RabbitMQ\Rpc\Rpc;
use Ipunkt\RabbitMQ\Sender\Exceptions\NoRpcAttachedException;

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
     * @var bool
     */
    private $isRpcCall;

    /**
     * @var Rpc
     */
    private $rpc;

    /**
     * @var Closure
     */
    private $rpcBuilder;

    /**
     * RabbitMQ constructor.
     * @param AmqpConnectionFactory $connectionFactory
     */
    public function __construct(AmqpConnectionFactory $connectionFactory) {
        $this->connectionFactory = $connectionFactory;

        $this->rpcBuilder = function() {
            /**
             * @var Rpc $rpc
             */
            $rpc = app(Rpc::class);

            $rpc->setAnswerField('answer_to_queue');

            return $rpc;
        };
        $this->dontRenameQueues();
        $this->dontRenameExchanges();
    }

    public function publish(array $data)
    {
        $this->data = $data;
        return $this;
    }

    public function onExchange($exchangeName, $routingKey)
    {
        $this->connect();

        $exchange = $this->buildExchange($exchangeName);

        $message = new AmqpMessage();
        $message->setRoutingKey($routingKey);

        Log::debug('RabbitMQ message on exchange', [
            'exchange' => $exchangeName,
            'routing-key' => $routingKey,
            'data' => $this->data
        ]);
        $this->send($exchange, $message);
    }

    public function asRpc() {
        $this->connect();

        $rpcBuilder = $this->rpcBuilder;
        $this->rpc = $rpcBuilder();
        $this->rpc
            ->setContext($this->context)
            ->createAnswerQueue()
            ->appendAnswerQueueToData();
        $this->isRpcCall = true;
        return $this;
    }

    /**
     * Get the current Rpc to preserve it across future rpc calls
     *
     * @return Rpc
     */
    public function getRpc()
    {
        return $this->rpc;
    }

    public function closeRpc()
    {
        $this->rpc = null;
        $this->isRpcCall = false;
    }

    public function waitForResponse()
    {
        $this->assertHasRpc();

        $this->rpc->listen();

        return $this;

    }

    public function getResponse()
    {
        return $this->rpc->getMessage();
    }

    public function onQueue($queueName)
    {
        $this->connect();

        $queue = $this->buildQueue($queueName);

        Log::debug('RabbitMQ message on queue', [
            'queue' => $queueName,
            'data' => $this->data
        ]);
        $this->send($queue);
    }

    private function send( Destination $to, Message $message = null ) {
        if( !$message instanceof AmqpMessage )
            $message = new AmqpMessage();

        $this->appendRpcData();
        $message->setBody( json_encode($this->data) );
        $message->setContentType('application/json');

        $producer = $this->context->createProducer();


        event(new MessageSending($message));
        $producer->send($to, $message);
        event(new MessageSent($message));
        $this->resetRpc();
    }

    private function connect() {
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

    private function exchangeExists($exchangeName) {
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

    private function resetRpc()
    {
        $this->isRpcCall = false;
        // keep rpc to allow listening to it
    }

    private function assertHasRpc()
    {
        if($this->rpc === null) {
            throw new NoRpcAttachedException();
        }
    }

    private function appendRpcData()
    {
        if($this->rpc === null)
            return;

        $this->rpc->setData($this->data)
            ->appendAnswerQueueToData();
        $this->data = $this->rpc->getData();
    }

}