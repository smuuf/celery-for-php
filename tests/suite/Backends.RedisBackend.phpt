<?php

use Tester\Assert;

use Smuuf\CeleryForPhp\State;
use Smuuf\CeleryForPhp\TaskMetaResult;
use Smuuf\CeleryForPhp\AsyncResult;
use Smuuf\CeleryForPhp\Backends\RedisBackend;

require __DIR__ . '/../bootstrap.php';

$redisDriver = TestCeleryFactory::getPredisRedisDriver();
$backend = new RedisBackend($redisDriver);

$testTaskId = 'celery-for-php-tests-very_task_id_123456789';
$testResultKey = RedisBackend::TASK_KEYPREFIX . $testTaskId;

// Delete test result from previous test runs.
$redisDriver->del($testResultKey);

//
// Fetching task metadata without any present will result in a void "pending"
// TaskMetaResult object.
//

$meta = $backend->getTaskMetaResult($testTaskId);
Assert::type(TaskMetaResult::class, $meta);
Assert::same(State::PENDING, $meta->getState());
Assert::same(null, $meta->getResult());

//
// Inject some test meta data for the task.
//

$backend->storeResult(
	$testTaskId,
	['pid' => 123456, 'hostname' => 'some_machine'],
	State::STARTED,
);

//
// Celery backend can now fetch real (test) metadata.
//

$backend = new RedisBackend($redisDriver);
$meta = $backend->getTaskMetaResult($testTaskId);

Assert::type(TaskMetaResult::class, $meta);
Assert::same(State::STARTED, $meta->getState());
Assert::same([
	'pid' => 123456,
	'hostname' => 'some_machine',
], $meta->getResult());

//
// AsyncResult has the same data via the Celery backend.
//

$result = new AsyncResult($testTaskId, $backend);
Assert::false($result->isReady());
Assert::false($result->isSuccessful());
Assert::false($result->isFailed());
Assert::same(State::STARTED, $result->getState());
Assert::same([
	'pid' => 123456,
	'hostname' => 'some_machine',
], $result->getResult());

//
// After forgetting the result, the void "pending" state is returned again.
//

$backend->forgetResult($testTaskId);
$meta = $backend->getTaskMetaResult($testTaskId);
Assert::type(TaskMetaResult::class, $meta);
Assert::same(State::PENDING, $meta->getState());
Assert::same(null, $meta->getResult());
