<?php

namespace Ipunkt\RabbitMQ\Commands;

use Interop\Amqp\AmqpConnectionFactory;
use Illuminate\Console\Command;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpMessage;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\Impl\AmqpBind;
use Interop\Queue\Consumer;
use Ipunkt\RabbitMQ\Connector\Connector;
use Ipunkt\RabbitMQ\Connector\SecondSleeper;
use Ipunkt\RabbitMQ\Events\MessageCausedException;
use Ipunkt\RabbitMQ\Events\MessageProcessed;
use Ipunkt\RabbitMQ\Events\MessageReceived;
use Ipunkt\RabbitMQ\MessageHandler\MessageHandler;
use Symfony\Component\Console\Output\Output;
use Throwable;

class RabbitMQListenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:listen';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen to rabbitmq';

    /**
     * @var \Enqueue\AmqpExt\AmqpContext|\Interop\Queue\Context
     */
    private $context;

    /**
     * @var \Interop\Amqp\AmqpTopic|\Interop\Queue\Topic
     */
    private $topic;

    /**
     * @var \Interop\Amqp\AmqpQueue|\Interop\Queue\Queue
     */
    private $queue;

    /**
     * @var \Enqueue\AmqpExt\AmqpConsumer|Consumer
     */
    private $consumer;
    /**
     * @var \Interop\Queue\SubscriptionConsumer
     */
    private $subscriptionConsumer;

    /**
     * @var MessageHandler
     */
    protected $messageHandler;

    /**
     * @var string
     */
    private $queueName = '';

    /**
     * @var string
     */
    private $exchangeName = '';

    /**
     * @var string[]
     */
    private $routingKeys = [];

    /**
 * @var Connector
 */private $connector;

    /**
     * Create a new command instance.
     *
     * @param MessageHandler $messageHandler
     * @param Connector $connector
     */
    public function __construct(MessageHandler $messageHandler, Connector $connector)
    {
        parent::__construct();
        $this->messageHandler = $messageHandler;
        $this->connector = $connector;
    }

    /**
     * Execute the console command.
     *
     * @param AmqpConnectionFactory $connectionFactory
     * @return mixed
     * @throws \Interop\Queue\Exception\Exception
     */
    public function handle(AmqpConnectionFactory $connectionFactory)
    {
        $this->connectContext($connectionFactory);

        $this->buildTopic();

        $this->buildQueue();

        $this->bindQueue();

        $this->buildConsumer();

        return $this->consume();
    }

    private function connectContext(AmqpConnectionFactory $connectionFactory)
    {
        $this->context = $connectionFactory->createContext();

        $this->connector
            ->setContext($this->context)
            ->setConnectCallback(function(AmqpContext $context) {
                $context->setQos(0, 0, true);
            })->connect();
    }

    private function buildTopic(): void
    {
        if( !$this->hasExchange() )
            return;

        $this->topic = $this->context->createTopic( $this->exchangeName );
        $this->topic->setType(AmqpTopic::TYPE_TOPIC);
        $this->topic->setFlags(AmqpTopic::FLAG_DURABLE);
        $this->context->declareTopic($this->topic);
        $this->info('Declared Exchange '.$this->exchangeName);
    }

    private function buildQueue(): void
    {
        $this->queue = $this->context->createQueue($this->queueName);
        $this->queue->setFlags(AmqpQueue::FLAG_DURABLE);
        $this->context->declareQueue($this->queue);
        $this->info('Declared Queue '.$this->queueName);
    }

    private function bindQueue(): void
    {
        if( !$this->hasExchange() )
            return;

        foreach ($this->routingKeys as $routingKey) {
            $this->info('Bound Queue '.$this->queueName.' to Exchange '.$this->exchangeName.' with '.$routingKey);
            $this->context->bind(new AmqpBind($this->topic, $this->queue, $routingKey));
        }
    }

    private function buildConsumer()
    {
        $this->consumer = $this->context->createConsumer($this->queue);
        $this->subscriptionConsumer = $this->context->createSubscriptionConsumer();
        $this->subscriptionConsumer->subscribe($this->consumer, function (AmqpMessage $message, Consumer $consumer) {
            $this->info('Received message '.$message->getRoutingKey() );
            $this->info('Message Content '.$message->getBody(), Output::VERBOSITY_VERBOSE);

            $this->messageReceivedEvent($message);
            try {
                $handled = $this->messageHandler
                    ->setMessage($message)
                    ->setConsumer($consumer)
                    ->setContext($this->context)
                    ->setQueue($this->queue)
                    ->handle();
            } catch(Throwable $t) {
                $this->messageCausedExceptionEvent($message, $t);
                throw $t;
            }
            $this->messageProcessedEvent($message, $handled);

            return $handled;
        });
    }

    private function consume()
    {
        $this->info('Starting to listen');
        $this->subscriptionConsumer->consume();
    }

    public function registerHandler(string $routingKey, string $class)
    {
        $this->routingKeys[] = $routingKey;
        $this->routingKeys = array_unique($this->routingKeys);
        $this->messageHandler->registerHandler($routingKey, $class);
    }

    public function setQueue(string $queue)
    {
        $this->queueName = $queue;
    }

    public function setExchange(string $exchange)
    {
        $this->exchangeName = $exchange;
    }

    private function hasExchange()
    {
        $exchangeNameNotEmpty = !empty($this->exchangeName);
        return $exchangeNameNotEmpty;
    }

    private function messageReceivedEvent(AmqpMessage $message)
    {
        try {
            event(new MessageReceived($message));
        } catch(Throwable $t) {
            echo "FATAL: Exception during MessageReceived Handler".PHP_EOL;
            var_dump($t);
            throw $t;
        }
    }

    private function messageProcessedEvent(AmqpMessage $message, bool $handled)
    {

        try {
            event(new MessageProcessed($message, $handled));
        } catch(Throwable $t) {
            echo "FATAL: Exception during MessageProcessed Handler".PHP_EOL;
            throw $t;
        }
    }

    private function messageCausedExceptionEvent(AmqpMessage $message, Throwable $messageThrowable)
    {
        try {
            event(new MessageCausedException($message, $messageThrowable));
        } catch(Throwable $t) {
            echo "FATAL: Exception during MessageCausedException Handler".PHP_EOL;
            var_dump($t);
        }
    }

    public function sleepOnError($secondsToSleep)
    {
        $this->connector->setSleeper(new SecondSleeper($secondsToSleep));
    }
}
