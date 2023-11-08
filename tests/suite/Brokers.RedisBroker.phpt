<?php

use Tester\Assert;

use Smuuf\CeleryForPhp\DeliveryInfo;
use Smuuf\CeleryForPhp\Brokers\RedisBroker;
use Smuuf\CeleryForPhp\Drivers\PredisRedisDriver;
use Smuuf\CeleryForPhp\Messaging\CeleryMessage;
use Smuuf\CeleryForPhp\Serializers\JsonSerializer;

require __DIR__ . '/../bootstrap.php';

function _prepare_random_queue(): string {

	global $redisDriver;
	$queue = 'celery-for-php.test-queue.' . md5(random_bytes(8));

	// Make sure the Redis key does not exist first.
	$redisDriver->del($queue);
	return $queue;

}

$redisDriver = TestCeleryFactory::getPredisRedisDriver();
$broker = new RedisBroker($redisDriver);
$predis = Assert::with($redisDriver, function() {
	/** @var PredisRedisDriver $this */
	return $this->predis;
});

// Whatever message. DeliveryInfo is important.
$queue = _prepare_random_queue();
$msg = new CeleryMessage([], [], [], new JsonSerializer());
$broker->publish($msg, new DeliveryInfo(routingKey: $queue));
Assert::same(1, $predis->llen($queue), 'Message was correctly posted into a queue');
$broker->publish($msg, new DeliveryInfo(routingKey: $queue));
Assert::same(2, $predis->llen($queue), 'Message was correctly posted into a queue');

// Exchange (not routing key) - will not be sent directly into the queue.
$queue2 = _prepare_random_queue();
$broker->publish($msg, new DeliveryInfo(exchange: $queue2));
Assert::same(0, $predis->llen($queue2), 'Message was correctly not posted into a queue');
