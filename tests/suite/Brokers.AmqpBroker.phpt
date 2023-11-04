<?php

use Tester\Assert;

use Smuuf\CeleryForPhp\DeliveryInfo;
use Smuuf\CeleryForPhp\Brokers\AmqpBroker;
use Smuuf\CeleryForPhp\Drivers\PhpAmqpLibAmqpDriver;
use Smuuf\CeleryForPhp\Messaging\CeleryMessage;
use Smuuf\CeleryForPhp\Serializers\JsonSerializer;
use PhpAmqpLib\Connection\AMQPSSLConnection;

require __DIR__ . '/../bootstrap.php';

function _setup_test_queue(): string {

	global $amqpDriver;

	_drop_test_queue();

	$queue = 'celery-for-php.test-queue';

	$amqpDriver->purgeQueue($queue);

	return $queue;

}

function _drop_test_queue(): string {

	global $amqpDriver;
	$queue = 'celery-for-php.test-queue';

	$amqpDriver->deleteQueue($queue);
	$amqpDriver->deleteExchange($queue);

	return $queue;

}

$amqpAuth = CeleryFactory::getAmqpConnectionConfig();

$amqpConn = new AMQPSSLConnection(
	$amqpAuth['host'],
	$amqpAuth['port'],
	$amqpAuth['user'],
	$amqpAuth['password'],
	$amqpAuth['vhost'],
	['verify_peer' => false],
);

$amqpDriver = new PhpAmqpLibAmqpDriver($amqpConn);
$broker = new AmqpBroker($amqpDriver);

$queue = _setup_test_queue();
$msg = new CeleryMessage([], [], [], new JsonSerializer());

$broker->publish($msg, new DeliveryInfo(routingKey: $queue));
Assert::same(1, $amqpDriver->queueLength($queue), 'Message was correctly posted into a queue');
$broker->publish($msg, new DeliveryInfo(routingKey: $queue));
Assert::same(2, $amqpDriver->queueLength($queue), 'Message was correctly posted into a queue');

// Exchange (not routing key) - will not be sent directly into the queue.
$queue2 = _setup_test_queue();
$broker->publish($msg, new DeliveryInfo(exchange: $queue2));
Assert::same(0, $amqpDriver->queueLength($queue2), 'Message was correctly not posted into a queue');

_drop_test_queue();
