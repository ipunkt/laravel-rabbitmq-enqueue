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
use Ipunkt\RabbitMQ\LegacyExchange\LegacyExchange;
use Ipunkt\RabbitMQ\MessageHandler\MessageHandler;
use Ipunkt\RabbitMQ\TopicBinder\TopicBinder;
use Symfony\Component\Console\Output\Output;
use Throwable;

class RabbitMQListenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:listen {--w|wait=0}';

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
 * @var Connector
 */private $connector;
    /**
     * @var TopicBinder
     */
    private $topicBinder;
    /**
     * @var LegacyExchange
     */
    private $legacyExchange;

    /**
     * Create a new command instance.
     *
     * @param MessageHandler $messageHandler
     * @param Connector $connector
     * @param TopicBinder $topicBinder
     * @param LegacyExchange $legacyExchange
     */
    public function __construct(MessageHandler $messageHandler, Connector $connector, TopicBinder $topicBinder, LegacyExchange $legacyExchange)
    {
        parent::__construct();
        $this->messageHandler = $messageHandler;
        $this->connector = $connector;
        $this->topicBinder = $topicBinder;
        $this->legacyExchange = $legacyExchange;
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
        $this->initialWait();

        $this->connectContext($connectionFactory);

        $this->legacyExchange
            ->setTopicBinder($this->topicBinder)
            ->mapLegacyExchangeToBinder();

        $this->buildTopics();

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

    private function buildTopics(): void
    {
        $this->topicBinder
            ->setContext($this->context)
            ->setBuiltCallback(function($exchangeName) {
                $this->info('Declared Exchange '.$exchangeName);
            })
            ->build();
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
        $this->topicBinder
            ->setContext($this->context)
            ->setQueue($this->queue)
            ->setBoundCallback(function($queueName, $exchangeName, $routingKey) {
                $this->info('Bound Queue '.$queueName.' to Exchange '.$exchangeName.' with '.$routingKey);
            })
            ->bind();

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
        $this->messageHandler->registerHandler($routingKey, $class);
    }

    public function setQueue(string $queue)
    {
        $this->queueName = $queue;
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

    public function setMaximumConnectionAttempts($connectionAttempts)
    {
        $this->connector->setMaximumAttempts($connectionAttempts);
    }

    private function initialWait()
    {
        $waitTime = $this->option('wait');
        sleep($waitTime);
    }

    public function addBinding($exchangeName, $routingKey)
    {
        $this->topicBinder->addBinding($exchangeName, $routingKey);
    }

    public function setExchange(string $exchangeName)
    {
        $this->legacyExchange->setExchangeName($exchangeName);
    }

}
