<?php namespace Ipunkt\RabbitMQ\Commands;

use Interop\Amqp\AmqpQueue;
use Illuminate\Console\Command;
use Interop\Amqp\AmqpConnectionFactory;
use Interop\Amqp\AmqpContext;
use Ipunkt\RabbitMQ\Connector\Connector;
use Ipunkt\RabbitMQ\TopicBinder\TopicBinder;

/**
 * Class RabbitMQSetupTestCommand
 * @package Ipunkt\RabbitMQ\Commands
 */
class RabbitMQSetupTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:setup-test {--w|wait=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create all known exchanges and a test queue subscribing to all their messages';

    /**
     * @var \Interop\Amqp\AmqpQueue|\Interop\Queue\Queue
     */
    private $queue;

    /**
     * @var \Enqueue\AmqpExt\AmqpContext|\Interop\Queue\Context
     */
    private $context;

    /**
     * @var Connector
     */
    private $connector;
    /**
     * @var TopicBinder
     */
    private $topicBinder;

    /**
     * RabbitMQSetupTestCommand constructor.
     * @param Connector $connector
     * @param TopicBinder $topicBinder
     */
    public function __construct(Connector $connector, TopicBinder $topicBinder) {
        parent::__construct();
        $this->connector = $connector;
        $this->topicBinder = $topicBinder;
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

        $this->buildTopics();

        $this->buildQueue();

        $this->bindQueue();

        return 0;
    }

    private function initialWait()
    {
        $waitTime = $this->option('wait');
        sleep($waitTime);
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
        $this->queue = $this->context->createQueue('test');
        $this->queue->setFlags(AmqpQueue::FLAG_DURABLE);
        $this->context->declareQueue($this->queue);
        $this->info('Declared Queue test');
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

    public function addExchange($exchangeName)
    {
        $this->topicBinder->addBinding($exchangeName, '#');
    }
}