<?php namespace Ipunkt\RabbitMQ\MessageHandler;

use Enqueue\AmqpTools\RabbitMqDlxDelayStrategy;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Interop\Amqp\AmqpMessage;
use Interop\Queue\Consumer;
use Interop\Queue\Processor;
use Ipunkt\RabbitMQ\Exceptions\DropsMessage;
use Throwable;

/**
 * Class MessageHandler
 * @package Ipunkt\RabbitMQ\MessageHandler
 */
class MessageHandler
{

    /**
     * @var AmqpMessage
     */
    private $message;

    /**
     * @var Consumer
     */
    private $consumer;

    /**
     * @var \Enqueue\AmqpExt\AmqpContext|\Interop\Queue\Context
     */
    private $context;

    /**
     * @var \Interop\Amqp\AmqpQueue|\Interop\Queue\Queue
     */
    private $queue;

    /**
     * routingKey => HandlerClass
     *
     * @var string[]
     */
    protected $handlersClasspathes = [];

    /**
     * Registering a handler for this routing key will cause it to be called when no other handler takes the message
     *
     * @var string
     */
    protected $defaultHandlerRoutingKey = '#';

    private $routingKey = '';

    public function handle()
    {
        $this->parseRoutingKey();

        if( !$this->hasHandler() ) {
            $this->consumer->reject($this->message);
            return false;
        }

        try {
            $result = $this->process();
        } catch(DropsMessage $e) {
            $result = Processor::REJECT;
        } catch(Throwable $throwable) {
            $this->requeueMessage();
            Log::debug('Message requeued');
            throw $throwable;
        }
        switch ($result) {
            case Processor::ACK:
                $this->consumer->acknowledge($this->message);
                Log::debug('Message acknowledged');
                return true;
            case Processor::REJECT:
                $this->consumer->reject($this->message);
                Log::debug('Message rejected');
                return true;
            case Processor::REQUEUE:
                $this->requeueMessage();
                Log::debug('Message requeued');
                return true;
        }

        return true;
    }

    private function parseRoutingKey()
    {
        $this->routingKey = $this->message->getProperty('redelivered-routing-key');
        if (empty($this->routingKey)) {
            $this->routingKey = $this->message->getRoutingKey();
        }
        $this->message->setRoutingKey($this->routingKey);
    }

    private function hasHandler() {
        $hasSpecificHanlder = array_key_exists($this->routingKey, $this->handlersClasspathes);
        $hasDefaultHandler = array_key_exists($this->defaultHandlerRoutingKey, $this->handlersClasspathes);

        return $hasSpecificHanlder || $hasDefaultHandler;
    }

    private function process()
    {
        $handlerClasspath =  $this->getHandlerClasspath();

        /**
         * @var Handler $handler
         */
        $handler = app($handlerClasspath);

        return $handler->handle($this->message);
    }

    private function getHandlerClasspath() {
        $defaultHandlerClasspath = Arr::get($this->handlersClasspathes, $this->defaultHandlerRoutingKey, null);

        return Arr::get($this->handlersClasspathes, $this->routingKey, $defaultHandlerClasspath);
    }

    public function registerHandler(string $routingKey, string $class)
    {
        $this->handlersClasspathes[$routingKey] = $class;
    }

    public function setMessage(AmqpMessage $message)
    {
        $this->message = $message;
        return $this;
    }

    public function setConsumer(Consumer $consumer)
    {
        $this->consumer = $consumer;
        return $this;
    }

    /**
     * @param \Enqueue\AmqpExt\AmqpContext|\Interop\Queue\Context $context
     * @return MessageHandler
     */
    public function setContext($context)
    {
        $this->context = $context;
        return $this;
    }

    /**
     * @param \Interop\Amqp\AmqpQueue|\Interop\Queue\Queue $queue
     * @return MessageHandler
     */
    public function setQueue($queue)
    {
        $this->queue = $queue;
        return $this;
    }

    protected function requeueMessage(): void
    {
        $resendMessage = $this->context->createMessage();
        $resendMessage->setContentEncoding($this->message->getContentEncoding());
        $resendMessage->setContentType($this->message->getContentType());
        $resendMessage->setBody($this->message->getBody());
        $resendMessage->setRedelivered(true);
        $resendMessage->setProperty('redelivered', $this->message->getProperty('redelivered', 0) + 1);
        $resendMessage->setProperty('redelivered-routing-key', $this->message->getRoutingKey());
        $producer = $this->context->createProducer();
        $producer->setDelayStrategy(new RabbitMqDlxDelayStrategy());
        $producer->setDeliveryDelay(10000);
        $producer->send($this->queue, $resendMessage);
        $this->consumer->acknowledge($this->message);
    }
}