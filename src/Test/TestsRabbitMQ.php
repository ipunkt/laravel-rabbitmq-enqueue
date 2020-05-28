<?php


namespace Ipunkt\RabbitMQ\Test;


use Interop\Amqp\AmqpMessage;
use Ipunkt\RabbitMQ\MessageHandler\MessageHandler;

trait TestsRabbitMQ
{
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

    /**
     * @param string $event
     * @param array $expectedData
     */
    protected function expectMessage( $event, $expectedData ) {
        $this->rabbitMQ->shouldReceive( 'onExchange' )->with( 'default-exchange', $event )->once()->andReturnSelf();
        $this->rabbitMQ->shouldReceive( 'publish' )->withArgs(  function ( $data ) use ( $expectedData ) {
            $anyMatch = false;

            foreach ( $expectedData as $expectedKey => $expectedValue ) {

                if ( !array_key_exists( $expectedKey, $data ) )
                    return false;

                if( is_float($data[ $expectedKey ]))
                    $data[ $expectedKey ] = round($data[ $expectedKey ], 9);

                if( is_float($expectedValue))
                    $expectedValue = round($expectedValue, 9);

                if ( $data[ $expectedKey ] != $expectedValue )
                    return false;

                $anyMatch = true;
            }

            return $anyMatch;
        } )->once()->andReturnSelf();
    }
}