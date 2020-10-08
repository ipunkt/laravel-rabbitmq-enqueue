<?php


namespace Ipunkt\RabbitMQ\Test;


use Interop\Amqp\AmqpMessage;
use Ipunkt\RabbitMQ\Sender\RabbitMQ;
use Mockery;

trait TestsRabbitMQ
{

    /**
     * @var string
     */
    protected $expectedRoutingKey = '';

    /**
     * @var string
     */
    protected $expectedExchangeName = '';

    public function sendMessage(AmqpMessage $message)
    {
        /**
         * @var TestHelper $testHelper
         */
        $testHelper = $this->app->make(TestHelper::class);
        $testHelper->send($message);
    }

    /**
     * Create an AmqpMessage to pass to sendMessage
     *
     * @param $routingKey
     * @param array|string $data a string will be passed directly as body, an array will be encoded as json
     */
    protected function makeMessage(string $routingKey, $data) {
        if( is_array($data) )
            $data = json_encode($data);

        $message = new \Interop\Amqp\Impl\AmqpMessage();
        $message->setRoutingKey($routingKey);
        $message->setBody($data);

        return $message;
    }

    protected function setUpRabbitMQ() {
        $this->rabbitMQ = Mockery::mock( RabbitMQ::class );
        $this->rabbitMQ->shouldIgnoreMissing($this->rabbitMQ);
        $this->app->instance(RabbitMQ::class, $this->rabbitMQ);
    }

    protected function expectEvent($routingKey)
    {
        $this->expectedRoutingKey = $routingKey;
        return $this;
    }

    protected function onExchange($exchangeName)
    {
        $this->expectedExchangeName = $exchangeName;
        return $this;
    }

    /**
     * @param string $event
     * @param array $expectedData
     */
    protected function withMessage( $expectedData ) {
        $this->rabbitMQ->shouldReceive( 'onExchange' )->with( $this->expectedExchangeName, $this->expectedRoutingKey )->once()->andReturnSelf();
        $this->rabbitMQ->shouldReceive( 'publish' )->withArgs(  function ( $data ) use ( $expectedData ) {
            return $this->compareArray($expectedData, $data);
        } )->once()->andReturnSelf();
    }

    /**
     * @param string $event
     * @param array $expectedData
     */
    protected function withAnyMessage() {
      $this->rabbitMQ->shouldReceive( 'onExchange' )
                     ->with( 
                       $this->expectedExchangeName,
                       $this->expectedRoutingKey
                     )
                     ->once()
                     ->andReturnSelf();
      $this->rabbitMQ->shouldReceive( 'publish' );
    }

    private function compareArray($expectedData, $data)
    {
      $anyMatch = false;
      foreach ( $expectedData as $expectedKey => $expectedValue ) {

        if ( !array_key_exists( $expectedKey, $data ) )
          return false;

        if( $this->compare($expectedValue, $data[$expectedKey]) ) {
          $anyMatch = true;
        }
      }
      
      return $anyMatch;
    }

    private function compare($expectedValue, $actualValue)
    {
      if( is_float($actualValue))
        $actualValue = round($actualValue, 9);

      if( is_float($expectedValue))
        $expectedValue = round($expectedValue, 9);

      if( is_array($expectedValue) && is_array($actualValue) ) {
        return $this->compareArray($expectedValue, $actualValue);
      }

      if ( $actualValue != $expectedValue ) {
        return false;
      }

      return true;
    }
}
