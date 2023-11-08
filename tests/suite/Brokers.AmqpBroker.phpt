<?php

use Tester\Assert;

use Smuuf\CeleryForPhp\DeliveryInfo;
use Smuuf\CeleryForPhp\Brokers\AmqpBroker;
use Smuuf\CeleryForPhp\Messaging\CeleryMessage;
use Smuuf\CeleryForPhp\Serializers\JsonSerializer;

require __DIR__ . '/../bootstrap.php';

function _setup_test_queue(): string {

	global $driver;

	_drop_test_queue();

	$queue = 'celery-for-php.test-queue';
	$driver->purgeQueue($queue);
	return $queue;

}

function _drop_test_queue(): string {

	global $driver;
	$queue = 'celery-for-php.test-queue';

	$driver->deleteQueue($queue);
	$driver->deleteExchange($queue);

	return $queue;

}


$driver = TestCeleryFactory::getPhpAmqpLibAmqpDriver();
$broker = new AmqpBroker($driver);

$queue = _setup_test_queue();
$msg = new CeleryMessage([], [], [], new JsonSerializer());

$broker->publish($msg, new DeliveryInfo(routingKey: $queue));
Assert::same(1, $driver->queueLength($queue), 'Message was correctly posted into a queue');
$broker->publish($msg, new DeliveryInfo(routingKey: $queue));
Assert::same(2, $driver->queueLength($queue), 'Message was correctly posted into a queue');

// Exchange (not routing key) - will not be sent directly into the queue.
$queue2 = _setup_test_queue();
$broker->publish($msg, new DeliveryInfo(exchange: $queue2));
Assert::same(0, $driver->queueLength($queue2), 'Message was correctly not posted into a queue');

_drop_test_queue();
