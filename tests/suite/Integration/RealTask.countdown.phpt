<?php

use Tester\Assert;
use Predis\Client as PredisClient;

use Smuuf\CeleryForPhp\Celery;
use Smuuf\CeleryForPhp\AsyncResult;
use Smuuf\CeleryForPhp\TaskSignature;
use Smuuf\CeleryForPhp\Backends\RedisBackend;
use Smuuf\CeleryForPhp\Brokers\RedisBroker;
use Smuuf\CeleryForPhp\Drivers\PredisDriver;
use Smuuf\CeleryForPhp\Exc\InvalidArgumentException;
use Smuuf\CeleryForPhp\State;

require __DIR__ . '/../../bootstrap.php';

$predis = new PredisClient(['host' => TestEnv::getRedisUri()]);
$redisDriver = new PredisDriver($predis);

$c = new Celery(
	new RedisBroker($redisDriver),
	new RedisBackend($redisDriver),
);

// Call real-life Python Celery's task.
$ts = new TaskSignature('main.add');

function test_task_with_countdown(int $countdown): void {

	global $ts, $c;

	$numA = random_int(1, 100);
	$numB = random_int(1, 100);

	$ts = $ts
		->setArgs([$numA, $numB])
		->setCountdown($countdown);

	$asyncResult = $c->sendTask($ts);
	Assert::type(AsyncResult::class, $asyncResult);
	Assert::same(State::PENDING, $asyncResult->getState(), "Task was sent with countdown, so it's PENDING");
	Assert::false($asyncResult->isReady(), "Task was sent with countdown, so it's not ready");

	sleep($countdown + 5);

	Assert::same(State::SUCCESS, $asyncResult->getState(), "Countdown passed and task should be a SUCCESS");
	Assert::true($asyncResult->isReady(), "Countdown passed and task should be 'ready'");
	Assert::same($numA + $numB, $asyncResult->getResult(), "The result of the task should be correct");

}

test_task_with_countdown(2);
test_task_with_countdown(0); // Zero is a valid countdown too.

Assert::exception(function() {
	test_task_with_countdown(-1);
}, InvalidArgumentException::class, '#cannot.*negative#i');
