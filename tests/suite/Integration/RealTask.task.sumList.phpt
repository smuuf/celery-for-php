<?php

use Tester\Assert;
use Predis\Client as PredisClient;

use Smuuf\CeleryForPhp\Celery;
use Smuuf\CeleryForPhp\TaskSignature;
use Smuuf\CeleryForPhp\Backends\RedisBackend;
use Smuuf\CeleryForPhp\Brokers\RedisBroker;
use Smuuf\CeleryForPhp\Drivers\PredisRedisDriver;
use Smuuf\CeleryForPhp\Exc\CeleryTaskException;
use Smuuf\CeleryForPhp\State;

require __DIR__ . '/../../bootstrap.php';

$predis = new PredisClient(CeleryFactory::getPredisConnectionConfig());
$redisDriver = new PredisRedisDriver($predis);

$c = new Celery(
	new RedisBroker($redisDriver),
	new RedisBackend($redisDriver),
);

// Call real-life Python Celery's task.
$ts = new TaskSignature('main.sum_list');
$ts = $ts->setArgs([[1, 2, 3]]);

$asyncResult = $c->sendTask($ts);
$asyncResult->get(); // Wait for result.
Assert::same(State::SUCCESS, $asyncResult->getState(), "We waited and now the state is a SUCCESS");
Assert::same(6, $asyncResult->getResult(), "Task returned expected result");

// Call real-life Python Celery's task.
$ts = new TaskSignature('main.sum_list');
$ts = $ts->setArgs([[1, 2, 3, 10, 20, 30]]);

$asyncResult = $c->sendTask($ts);
Assert::same(66, $asyncResult->get()); // Wait for result - which is returned directly.
Assert::same(State::SUCCESS, $asyncResult->getState(), "We waited and now the state is a SUCCESS");

// Call real-life Python Celery's task.
$ts = new TaskSignature('main.sum_list');
$ts = $ts->setArgs([[1, 'aaa', 'bbb']]);

//
// AsyncResult::getResult() does not convert Celery worker's exception to
// celery-for-php's TaskException, but we can still inspect the exception thrown
// by the task.
//

$asyncResult = $c->sendTask($ts);
while (!$asyncResult->isReady()) {
	sleep(1); // Wait for the task to fail (state will then be FAILED).
}
Assert::same(State::FAILURE, $asyncResult->getState());
Assert::true($asyncResult->isReady());
Assert::true($asyncResult->isFailed());
$tb = $asyncResult->getTraceback();
Assert::type('string', $tb, "Exception has occurred in the task, traceback should be present (as string)");
Assert::contains('celery-app/main.py', $tb, "Python source file should be present in the error's traceback");

//
// Send the same task again, but now use AsyncResult::get() to wait for the result.
// AsyncResult::get() also converts the detected exception into
// CeleryTaskException, which is then thrown.
//

$asyncResult = $c->sendTask($ts);

/** @var CeleryTaskException */
$exception = Assert::exception(function() use ($asyncResult) {
	$asyncResult->get();
}, CeleryTaskException::class, '#TypeError: unsupported operand#');

// And now we can still inspect the AsyncResult (and, for example, its error's traceback).
Assert::same(State::FAILURE, $asyncResult->getState());
Assert::true($asyncResult->isReady());
Assert::true($asyncResult->isFailed());

$tb = $asyncResult->getTraceback();
Assert::type('string', $tb, "Exception has occurred in the task, traceback should be present (as string)");
Assert::contains('celery-app/main.py', $tb, "Python source file should be present in the error's traceback");

// Inspect the CeleryTaskException.
Assert::same('builtins', $exception->getModule());
Assert::same('TypeError', $exception->getType());
Assert::same($tb, $exception->getTraceback());

// Retrieve another async result object of the same task ID.
$asyncResult2 = $c->getAsyncResult($asyncResult->getTaskId());

// Trying to get the result of failed task will reveal the underlying exception
// thrown inside the Celery worker.
Assert::exception(fn() => $asyncResult->getResult(), CeleryTaskException::class, '#TypeError#');
Assert::exception(fn() => $asyncResult2->getResult(), CeleryTaskException::class, '#TypeError#');

Assert::same($asyncResult->getState(), $asyncResult2->getState());
Assert::same($asyncResult->getTaskId(), $asyncResult2->getTaskId());
Assert::same($asyncResult->getTraceback(), $asyncResult2->getTraceback());

// Forget the AsyncResult (it will be completely removed from the result
// backend - as if the thing never happened).
$asyncResult->forget();
$asyncResult3 = $c->getAsyncResult($asyncResult->getTaskId());
Assert::same(State::PENDING, $asyncResult3->getState());
Assert::false($asyncResult3->isReady());
