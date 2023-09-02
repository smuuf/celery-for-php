<?php

use Tester\Assert;
use Predis\Client as PredisClient;

use Smuuf\CeleryForPhp\Celery;
use Smuuf\CeleryForPhp\TaskSignature;
use Smuuf\CeleryForPhp\Exc\CeleryTaskException;
use Smuuf\CeleryForPhp\Exc\InvalidArgumentException;
use Smuuf\CeleryForPhp\Backends\RedisBackend;
use Smuuf\CeleryForPhp\Brokers\RedisBroker;
use Smuuf\CeleryForPhp\Drivers\PredisDriver;

require __DIR__ . '/../../bootstrap.php';

$predis = new PredisClient(['host' => TestEnv::getRedisUri()]);
$redisDriver = new PredisDriver($predis);

$c = new Celery(
	new RedisBroker($redisDriver),
	new RedisBackend($redisDriver),
);

$base = new TaskSignature('main.just_wait');
$ts = $base->setArgs([1, 2, 3, 4]);

$asyncResult = $c->sendTask($ts);
Assert::exception(
	fn() => $asyncResult->get(),
	CeleryTaskException::class,
	'#just_wait.*takes from 1 to 2 positional arguments but 4 were given#',
);

$ts = $base->setKwargs(['vole' => 1]);
$asyncResult = $c->sendTask($ts);
Assert::exception(
	fn() => $asyncResult->get(),
	CeleryTaskException::class,
	'#just_wait.*got an unexpected keyword argument.*vole#',
);

Assert::exception(
	fn() => $base->setArgs(['this' => 'is_not_indexed']),
	InvalidArgumentException::class,
	'#Args must be passed as an indexed array#',
);
