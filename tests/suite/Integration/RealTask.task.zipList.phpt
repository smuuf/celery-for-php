<?php

use Tester\Assert;
use Predis\Client as PredisClient;

use Smuuf\CeleryForPhp\Celery;
use Smuuf\CeleryForPhp\TaskSignature;
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
$ts = (new TaskSignature('main.zip_dicts'))
	->setKwargs([
		'a' => [4, true, null, 'ahoj'],
		'b' => ['xxx', 'yyy', ['kve', 'bhe'], ['vole']],
	]);

$expectedZipped = [
	[4, 'xxx'],
	[true, 'yyy'],
	[null, ['kve', 'bhe']],
	['ahoj', ['vole']],
];

$asyncResult = $c->sendTask($ts);
$asyncResult->get(); // Wait for result.
Assert::same(State::SUCCESS, $asyncResult->getState(), "Task was sent with ETA, so it's PENDING");
Assert::same($expectedZipped, $asyncResult->getResult(), "Task returned expected result");
