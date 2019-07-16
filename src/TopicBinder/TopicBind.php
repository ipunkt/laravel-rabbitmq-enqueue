<?php namespace Ipunkt\RabbitMQ\TopicBinder;

use Interop\Amqp\AmqpTopic;
use Interop\Amqp\Impl\AmqpBind;

/**
 * Class TopicBind
 * @package Ipunkt\RabbitMQ\TopicBinder
 */
class TopicBind
{
    /**
     * @var string
     */
    protected $topicName;

    /**
     * @var  string
     */
    protected $routingKey;

    /**
     * @var \Interop\Amqp\AmqpTopic|\Interop\Queue\Topic
     */
    private $topic;

    /**
     * @var \Interop\Amqp\AmqpQueue|\Interop\Queue\Queue
     */
    protected $queue;

    /**
     * @var \Enqueue\AmqpExt\AmqpContext|\Interop\Queue\Context
     */
    protected $context;

    /**
     * @var \Closure
     */
    protected $builtCallback;

    /**
     * @var \Closure
     */
    protected $boundCallback;

    public function __construct() {
        $this->builtCallback = function() {};
        $this->boundCallback = function() {};
    }

    public function build()
    {
        $this->topic = $this->context->createTopic( $this->topicName );
        $this->topic->setType(AmqpTopic::TYPE_TOPIC);
        $this->topic->setFlags(AmqpTopic::FLAG_DURABLE);
        $this->context->declareTopic($this->topic);
        $callback = $this->builtCallback;
        $callback( $this->topic->getTopicName() );
    }

    public function bind()
    {
        $this->context->bind(new AmqpBind($this->topic, $this->queue, $this->routingKey));
        $callback = $this->boundCallback;
        $callback($this->queue->getQueueName(), $this->topic->getTopicName(), $this->routingKey);
    }

    /**
     * @param string $topicName
     * @return TopicBind
     */
    public function setTopicName(string $topicName): TopicBind
    {
        $this->topicName = $topicName;
        return $this;
    }

    /**
     * @param \Interop\Amqp\AmqpQueue|\Interop\Queue\Queue $queue
     * @return TopicBind
     */
    public function setQueue($queue)
    {
        $this->queue = $queue;
        return $this;
    }

    /**
     * @param \Enqueue\AmqpExt\AmqpContext|\Interop\Queue\Context $context
     * @return TopicBind
     */
    public function setContext($context)
    {
        $this->context = $context;
        return $this;
    }

    /**
     * @param string $routingKey
     * @return TopicBind
     */
    public function setRoutingKey(string $routingKey): TopicBind
    {
        $this->routingKey = $routingKey;
        return $this;
    }

    /**
     * @param \Closure $builtCallback
     * @return TopicBind
     */
    public function setBuiltCallback(\Closure $builtCallback): TopicBind
    {
        $this->builtCallback = $builtCallback;
        return $this;
    }

    /**
     * @param \Closure $boundCallback
     * @return TopicBind
     */
    public function setBoundCallback(\Closure $boundCallback): TopicBind
    {
        $this->boundCallback = $boundCallback;
        return $this;
    }



}