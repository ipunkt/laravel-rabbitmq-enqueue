<?php

namespace Ipunkt\RabbitMQ\Commands;

use Interop\Amqp\AmqpConnectionFactory;
use Illuminate\Console\Command;
use Interop\Amqp\AmqpMessage;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\Impl\AmqpBind;
use Interop\Queue\Consumer;
use Ipunkt\RabbitMQ\Events\MessageProcessed;
use Ipunkt\RabbitMQ\Events\MessageReceived;
use Ipunkt\RabbitMQ\MessageHandler\MessageHandler;
use Symfony\Component\Console\Output\Output;

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
     * Create a new command instance.
     *
     * @param MessageHandler $messageHandler
     */
    public function __construct(MessageHandler $messageHandler)
    {
        parent::__construct();
        $this->messageHandler = $messageHandler;
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
        $this->context = $connectionFactory->createContext();
        $this->context->setQos(0, 0, true);

        $this->buildTopic();

        $this->buildQueue();

        $this->bindQueue();

        $this->buildConsumer();

        return $this->consume();
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

            event(new MessageReceived($message));
            $response = $this->messageHandler
                ->setMessage($message)
                ->setConsumer($consumer)
                ->setContext($this->context)
                ->setQueue($this->queue)
                ->handle();
            event(new MessageProcessed($message, $response));

            return $response;
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
}
