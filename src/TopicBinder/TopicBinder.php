<?php namespace Ipunkt\RabbitMQ\TopicBinder;

use Closure;

/**
 * Class TopicBinder
 * @package Ipunkt\RabbitMQ\TopicBinder
 */
class TopicBinder
{

    /**
     * @var \Interop\Amqp\AmqpQueue|\Interop\Queue\Queue
     */
    protected $queue;

    /**
     * @var \Enqueue\AmqpExt\AmqpContext|\Interop\Queue\Context
     */
    protected $context;

    /**
     * @var TopicBind[]
     */
    protected $topicBinds = [];

    /**
     * @var Closure
     */
    protected $boundCallback;

    /**
     * @var Closure
     */
    protected $builtCallback;

    /**
     * @var Closure
     */
    protected $topicBindBuilder;

    public function __construct() {
        $this->boundCallback = function() {};
        $this->builtCallback = function() {};
        $this->topicBindBuilder = function() {
            return app(TopicBind::class);
        };
    }

    public function build()
    {
        foreach ($this->topicBinds as $topicBind) {
            $topicBind
                ->setContext($this->context)
                ->setQueue($this->queue)
                ->setBuiltCallback($this->builtCallback)
                ->build();
        }
    }

    public function bind()
    {
        foreach ($this->topicBinds as $topicBind) {
            $topicBind
                ->setContext($this->context)
                ->setQueue($this->queue)
                ->setBoundCallback($this->boundCallback)
                ->bind();
        }
    }

    /**
     * @param \Interop\Amqp\AmqpQueue|\Interop\Queue\Queue $queue
     * @return TopicBinder
     */
    public function setQueue($queue)
    {
        $this->queue = $queue;
        return $this;
    }

    /**
     * @param \Enqueue\AmqpExt\AmqpContext|\Interop\Queue\Context $context
     * @return TopicBinder
     */
    public function setContext($context)
    {
        $this->context = $context;
        return $this;
    }

    /**
     * @param string $exchange
     * @param string $routingKey
     */
    public function addBinding($exchange, $routingKey)
    {
        $topicBindBuilder = $this->topicBindBuilder;
        $this->topicBinds[] = $topicBindBuilder()->setTopicName($exchange)->setRoutingKey($routingKey);
    }

    /**
     * @param Closure $boundCallback
     * @return TopicBinder
     */
    public function setBoundCallback(Closure $boundCallback): TopicBinder
    {
        $this->boundCallback = $boundCallback;
        return $this;
    }

    /**
     * @param Closure $builtCallback
     * @return TopicBinder
     */
    public function setBuiltCallback(Closure $builtCallback): TopicBinder
    {
        $this->builtCallback = $builtCallback;
        return $this;
    }

}