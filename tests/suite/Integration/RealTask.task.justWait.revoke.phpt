<?php

use Tester\Assert;
use Predis\Client as PredisClient;

use Smuuf\CeleryForPhp\State;
use Smuuf\CeleryForPhp\Celery;
use Smuuf\CeleryForPhp\TaskSignature;
use Smuuf\CeleryForPhp\Brokers\RedisBroker;
use Smuuf\CeleryForPhp\Drivers\PredisDriver;
use Smuuf\CeleryForPhp\Backends\RedisBackend;
use Smuuf\CeleryForPhp\Exc\CeleryTaskException;
use Smuuf\CeleryForPhp\Helpers\Signals;

require __DIR__ . '/../../bootstrap.php';

$predis = new PredisClient(['host' => TestEnv::getRedisUri()]);
$redisDriver = new PredisDriver($predis);

$c = new Celery(
	new RedisBroker($redisDriver),
	new RedisBackend($redisDriver),
);

//
// Use "real life" Celery tasks.
//

$siNormal = new TaskSignature(
	taskName: 'main.just_wait',
	args: [5],
	kwargs: ['retval' => 'YAY'],
);

$siTracked = new TaskSignature(
	// The task itself has 'track_started=True' specified.
	taskName: 'main.just_wait_track_started',
	args: [5],
	kwargs: ['retval' => 'YAY_tracked_start'],
);

//
// Just revoke. (without track_started=True)
//

$asyncResult = $c->sendTask($siNormal);
sleep(1);
Assert::same(State::PENDING, $asyncResult->getState(), "Task is in progress (without track_started=True) (or not started yet), state is PENDING");
Assert::same(null, $asyncResult->getResult(), "Task has no result yet");

// Will not terminate (by default) already executing task.
$asyncResult->revoke();
sleep(1);
Assert::same(State::PENDING, $asyncResult->getState(), "A running task is PENDING and not REVOKED immediately");

// Wait for result.
$result = $asyncResult->get();
Assert::same(State::SUCCESS, $asyncResult->getState(), "State is a SUCCESS");
	Assert::same('YAY', $result, "Even revoked (without termination) task finished with a result");

//
// Just revoke. (with track_started=True)
//

$asyncResult = $c->sendTask($siTracked);
sleep(1);
Assert::same(State::STARTED, $asyncResult->getState(), "Task is in progress (with track_started=True), state is STARTED");
Assert::same(['pid', 'hostname'], array_keys($asyncResult->getResult()), "Task with track_started=True should have a default result containing PID and hostname");

// Will not terminate (by default) already executing task.
$asyncResult->revoke();
sleep(1);
Assert::same(State::STARTED, $asyncResult->getState(), "A running task is STARTED and not REVOKED immediately");

// Wait for result.
$result = $asyncResult->get();
Assert::same(State::SUCCESS, $asyncResult->getState(), "State is a SUCCESS");
	Assert::same('YAY_tracked_start', $result, "Even revoked (without termination) task finished with a result");

//
// Revoke + terminate (without track_started=True)
//

$asyncResult = $c->sendTask($siNormal);
sleep(1);
Assert::same(State::PENDING, $asyncResult->getState(), "Task is in progress (without track_started=True) (or not started yet), state is PENDING");
Assert::same(null, $asyncResult->getResult(), "Task has no result yet");

// Will forcefully terminate the task.
$asyncResult->revoke(terminate: true, signal: Signals::SIGKILL);
sleep(1);
Assert::same(State::REVOKED, $asyncResult->getState(), "State is immediately REVOKED");
// Trying to get the task's result for revoked task will reveal that Celery
// worker actually throws a TaskRevokedError exception when revoking+terminating
// a task.
Assert::exception(
	fn() => $asyncResult->getResult(),
	CeleryTaskException::class,
	'#TaskRevokedError#',
);

//
// Revoke + terminate (with track_started=True)
//

$asyncResult = $c->sendTask($siTracked);
sleep(1);
Assert::same(State::STARTED, $asyncResult->getState(), "Task with track_started=True reports status STARTED");
Assert::same(['pid', 'hostname'], array_keys($asyncResult->getResult()), "Task with track_started=True has a default result containing PID and hostname");
sleep(1);

// Will forcefully terminate the task.
$asyncResult->revoke(terminate: true, signal: Signals::SIGKILL);
sleep(1);
Assert::same(State::REVOKED, $asyncResult->getState(), "State is immediately REVOKED");
// Trying to get the task's result for revoked task will reveal that Celery
// worker actually throws a TaskRevokedError exception when revoking+terminating
// a task.
Assert::exception(
	fn() => $asyncResult->getResult(),
	CeleryTaskException::class,
	'#TaskRevokedError#',
);
