<?php namespace Ipunkt\RabbitMQ\LegacyExchange;

use Ipunkt\RabbitMQ\TopicBinder\TopicBinder;

/**
 * Class LegacyExchange
 * @package Ipunkt\RabbitMQ\LegacyExchange
 *
 * This classes sole purpose is to provide the functionality of the deprecated function RabbitMQListenCommand::setExchange
 * without polluting the command itself with legacy code
 */
class LegacyExchange
{

    /**
     * @var string
     */
    protected $exchangeName = '';

    /**
     * @var string[]
     */
    protected $routingKeys = [];

    /**
     * @var TopicBinder
     */
    protected $topicBinder;

    public function mapLegacyExchangeToBinder()
    {
        $hasExchangeName = empty($this->exchangeName);

        if(!$hasExchangeName) {
            return;
        }

        foreach ($this->routingKeys as $routingKey) {
            $this->topicBinder->addBinding($this->exchangeName, $routingKey);
        }
    }

    public function addRoutingKey($routingKey): LegacyExchange
    {
        $this->routingKeys[] = $routingKey;
        return $this;
    }

    /**
     * @param string $exchangeName
     * @return LegacyExchange
     */
    public function setExchangeName(string $exchangeName): LegacyExchange
    {
        $this->exchangeName = $exchangeName;
        return $this;
    }

    /**
     * @param TopicBinder $topicBinder
     * @return LegacyExchange
     */
    public function setTopicBinder(TopicBinder $topicBinder): LegacyExchange
    {
        $this->topicBinder = $topicBinder;
        return $this;
    }


}