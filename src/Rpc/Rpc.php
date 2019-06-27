<?php namespace Ipunkt\RabbitMQ\Rpc;

use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Queue;
use Ipunkt\RabbitMQ\Rpc\Exceptions\NoMessageReceivedException;
use Ipunkt\RabbitMQ\Rpc\Exceptions\QueueNotCreatedException;

/**
 * Class Rpc
 */
class Rpc
{

    /**
     * @var Context
     */
    protected  $context;

    /**
     * @var int
     */
    protected $timeout = 10;

    /**
     * @var string
     */
    protected $answerFieldName = 'answer-queue';

    /**
     * @var Queue
     */
    protected $queue;

    /**
     * @var Consumer
     */
    protected $consumer;

    /**
     * @var Message
     */
    protected $message;

    /**
     * @var array
     */
    protected $data = [];

    public function createAnswerQueue()
    {
        $this->queue = $this->context->createTemporaryQueue();
        $this->consumer = $this->context->createConsumer($this->queue);
        return $this;
    }

    public function listen()
    {
        $this->assertQueueExists('listen');

        $this->message = $this->consumer->receive($this->timeout);

        if($this->message === null)
            throw new NoMessageReceivedException();

        return $this;
    }

    public function appendAnswerQueueToData()
    {
        $this->assertQueueExists('append queue data');
        $this->data = array_merge($this->data, [
            $this->answerFieldName => $this->queue->getQueueName()
        ]);

        return $this;
    }

    /**
     * @param Context $context
     * @return Rpc
     */
    public function setContext(Context $context): Rpc
    {
        $this->context = $context;

        return $this;
    }

    /**
     * @param int $timeout
     * @return Rpc
     */
    public function setTimeout(int $timeout): Rpc
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * @return Message|null
     */
    public function getMessage(): Message
    {
        return $this->message;
    }

    /**
     * @param string $answerFieldName
     * @return Rpc
     */
    public function setAnswerField(string $answerFieldName): Rpc
    {
        $this->answerFieldName = $answerFieldName;
        return $this;
    }

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }


    public function getData()
    {
        return $this->data;
    }

    private function assertQueueExists(string $action)
    {
        if($this->queue === null)
            throw new QueueNotCreatedException($action);
    }

}