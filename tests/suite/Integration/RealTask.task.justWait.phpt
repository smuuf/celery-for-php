<?php

use Tester\Assert;
use Predis\Client as PredisClient;

use Smuuf\CeleryForPhp\Celery;
use Smuuf\CeleryForPhp\TaskSignature;
use Smuuf\CeleryForPhp\Exc\CeleryTimeoutException;
use Smuuf\CeleryForPhp\Backends\RedisBackend;
use Smuuf\CeleryForPhp\Brokers\RedisBroker;
use Smuuf\CeleryForPhp\Drivers\PredisDriver;
use Smuuf\CeleryForPhp\State;

require __DIR__ . '/../../bootstrap.php';

$predis = new PredisClient(['host' => TestEnv::getRedisUri()]);
$redisDriver = new PredisDriver($predis);

$c = new Celery(
	new RedisBroker($redisDriver),
	new RedisBackend($redisDriver),
);

// Call real-life Python Celery's task.
$ts = new TaskSignature('main.just_wait');
$ts = $ts->setArgs([1]);

$asyncResult = $c->sendTask($ts);
$asyncResult->get(); // Wait for result.
Assert::same(State::SUCCESS, $asyncResult->getState(), "We waited and now the state is a SUCCESS");
Assert::same(null, $asyncResult->getResult(), "Task returned expected result");

// Call real-life Python Celery's task.
$ts = new TaskSignature('main.just_wait');
$ts = $ts->setArgs([5, "my expected result"]);

$asyncResult = $c->sendTask($ts);

/** @var CeleryTimeoutException */
$exception = Assert::exception(function() use ($asyncResult) {
	$asyncResult->get(timeout: 2);
}, CeleryTimeoutException::class, '#task.*timed out.*seconds#i');

Assert::same(State::PENDING, $asyncResult->getState(), "We didn't wait long enough and the state is now a PENDING");
Assert::same(null, $asyncResult->getResult(), "Task has no result yet");

// But we can try again - now let's wait a bit longer.
$result = $asyncResult->get(timeout: 5);
Assert::same(State::SUCCESS, $asyncResult->getState(), "We didn't wait long enough and the state is now a SUCCESS");
Assert::same("my expected result", $asyncResult->getResult(), "Task now has the expected result");
Assert::same($result, $asyncResult->getResult());
