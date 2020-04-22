# Laravel Rabbitmq Enqueue
This package uses php-enqueue to provide RabbitMQ support for laravel.

It specifically uses the enqueue amqp-ext functionality and thus required the amqp php extension.

## Install
This package is available through composer

	composer require 'ipunkt/laravel-rabbitmq-enqueue:^1.0'

After installing publish the config and Provider through `vendor:publish`. It will create `config/rabbitmq.php` and
`app/Providers/RabbitMQProvider.php`.  

Add the RabbitMQProvider to your providers in the `config/app.php` and if necessary change its namespace from `App\` to
whatever your app uses.

### Environment
The default `config/rabbitmq.php` builds the dsn required to connect to rabbitmq through the following environment variables,
default values behind the name:
- RMQ_USERNAME - guest
- RMQ_PASSWORD - guest
- RMQ_HOST - rabbitmq
- RMQ_PORT - 5672
- RMQ_VHOST - /

## Use
### Listen
Listening is done by running the `rabbitmq:listen` command. It will connect to the dsn specified in the config file and
create all required queues, exchanges and bindings between the two.

Which queues, exchanges and bindings it both creates and listens to is defined in the published RabbitMQProvider

#### Waiting
Because `rabbitmq:listen` is expected to run in a cloud environment it brings the switch `-w SECONDS` to wait the given
amount of seconds before trying to connect, or even do anything at all.

It is possible to achieve the same by putting a sleep before the command but the frequency of this use case made me include
this small nod to cloud environments needing to set up dns and jaeger agents needing to start for ease of use.

#### Handler
Reacting to messages is done through a Handler. A handler is any class implementing the interface
`Ipunkt\RabbitMQ\MessageHandler\Handler`. Register your handlers to routing keys in the published RabbitMQProvider

It is handed an AmqpMessage object in which you will most likely want to decode the $message->getBody() from json as it
is currently the only format sent by the sender ofthis package.

#### Results
The following results can be handed back from your Handler to the command:
- `Processor::ACK` - the message was handled successfully. It will be dropped from the queue as success
- `Processor::REJECT` - the message does not concern this service or is malformed. It will be dropped from the queue as failure
- `Processor::REQUEUE` - the message is valid and expected to work but external circumstance prevents its handling at the
  current time. It will be moved to a waiting queue from which it will return after 10 seconds.
- Throwable or Exception - a Throwable or Exception reaching the command will be treated as `Processor::REQUEUE` before
  rethrowing the Throwable/Exception to the surrounding laravel code.  
  The logic here is that an error in the code caused this and thus the message is expected to process but can't until a
  new version is deployed.  
  Rethrowing the Exception should cause general exception handling to happen, for example notifying the developers through
  a system such as Sentry
- Exception implementing DropsMessage - Exceptions 

### Sending
Sending is provided through `Ipunkt\RabbitMQ\Sender\RabbitMQ`. It is not a facade so injecting it as a dependency is
recommended.

#### send to Exchange
Sending to an exchange is the expected use case as it provides lose coupling with the services that consume your messages

```php
$this-rabbitmq->publish([
	'some' => 'data',
	'serializable' => 'as',
	'json'
])->onExchange('exchange-name', 'routing-key')
```

#### send to Queue
Sending directly to a target queue is also possible

```php
$this-rabbitmq->publish([
	'some' => 'data',
	'serializable' => 'as',
	'json'
])->onQueue('queue-name')
```

## Why specifically RabbitMQ instead of the generalized functions
The generalized enqueue interfaces do not support routing key based routing.

The available SimpleClient simulates routing key based routing by using fanout exchanges and dropping unwanted messages
in the client code.  
It simulates a behaviour supported by RabbitMQ itself. And thus causes unecessary overhead in the services sharing
exchanges but only interested in certain routing keys.
