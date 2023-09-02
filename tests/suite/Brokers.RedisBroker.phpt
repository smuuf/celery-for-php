<?php

use Tester\Assert;

use Predis\Client as PredisClient;

use Smuuf\CeleryForPhp\TaskSignature;
use Smuuf\CeleryForPhp\Brokers\RedisBroker;
use Smuuf\CeleryForPhp\DeliveryInfo;
use Smuuf\CeleryForPhp\Drivers\PredisDriver;
use Smuuf\CeleryForPhp\Messaging\CeleryMessage;
use Smuuf\CeleryForPhp\Serializers\JsonSerializer;

require __DIR__ . '/../bootstrap.php';

function _prepare_random_queue(): string {

	global $predis;
	$queue = 'celery-for-php.test-queue.' . md5(random_bytes(8));

	// Make sure the Redis key does not exist first.
	$predis->del($queue);
	return $queue;

}

$predis = new PredisClient(['host' => TestEnv::getRedisUri()]);
$redisDriver = new PredisDriver($predis);
$broker = new RedisBroker($redisDriver);

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
