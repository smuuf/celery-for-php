<?php

use Tester\Assert;

use Smuuf\CeleryForPhp\State;
use Smuuf\CeleryForPhp\TaskMetaResult;
use Smuuf\CeleryForPhp\AsyncResult;
use Smuuf\CeleryForPhp\Exc\CeleryTimeoutException;
use Smuuf\CeleryForPhp\Exc\InvalidArgumentException;
use Smuuf\CeleryForPhp\Interfaces\IResultBackend;

require __DIR__ . '/../bootstrap.php';

class TempBackend implements IResultBackend {

	/** @var array Counter for method calls - for tests. */
	public static $callCounter = [
		'TempBackend::getTaskMetaResult' => 0,
		'TempBackend::storeResult' => 0,
		'TempBackend::forgetResult' => 0,
	];

	/** @var array */
	private $storage = [];

	public function getTaskMetaResult(string $taskId): TaskMetaResult {

		self::$callCounter[__METHOD__]++;

		return TaskMetaResult::fromArray(
			$taskId,
			$this->storage[$taskId] ?? []
		);

	}

	public function storeResult(
		string $taskId,
		$result,
		string $state,
		?string $traceback = null
	): void {

		self::$callCounter[__METHOD__]++;

		$dateDone = gmdate(\DateTime::ATOM);
		$this->storage[$taskId] = [
			'task_id' => $taskId,
			'result' => $result,
			'status' => $state,
			'date_done' => $dateDone,
			'traceback' => $traceback,
		];

	}

	public function forgetResult(string $taskId): void {

		self::$callCounter[__METHOD__]++;
		unset($this->storage[$taskId]);

	}

}

$backend = new TempBackend;

//
// Test getting a result from non-existing task ID.
//

$result = new AsyncResult('bogus', $backend);
Assert::same('bogus', $result->getTaskId());
Assert::same(State::PENDING, $result->getState());
Assert::false($result->isReady());
Assert::false($result->isSuccessful());
Assert::false($result->isFailed());

Assert::noError(function() use ($result) {
	$x = $result->getResult();
});


//
// Store some result into result backend.
//

const TASK_ID = '1234-abcdef-56789';
const TASK_STATE = 'SOME_RANDOM_STATE';
const TASK_RESULT = 'whatever result 🌭';
$backend->storeResult(
	TASK_ID,
	TASK_RESULT,
	TASK_STATE
);

//
// Test getting a real (test) AsyncResult.
//

$result = new AsyncResult(TASK_ID, $backend);
Assert::same(TASK_ID, $result->getTaskId());
Assert::same(TASK_STATE, $result->getState());
Assert::false($result->isReady());
Assert::false($result->isSuccessful());
Assert::false($result->isFailed());
Assert::same(TASK_RESULT, $result->getResult());

// 'SOME_RANDOM_STATE' is not recognized as a "ready state", so waiting for the
// task result will end up with exception.
Assert::exception(function() use ($result) {
	$result->get(3);
}, CeleryTimeoutException::class, '#timed out after 3 seconds#');


//
// AsyncResult can be serialized.
//

// Save this number for later.
$called = $backend::$callCounter['TempBackend::getTaskMetaResult'];

Assert::noError(function() use ($result) {

	$serialized = serialize($result);
	$restored = unserialize($serialized);
	Assert::same(TASK_STATE, $restored->getState());
	Assert::same(TASK_RESULT, $restored->getResult());

	// 'SOME_RANDOM_STATE' is not recognized as a "ready state", so waiting for
	// the task result will end up with exception.
	Assert::exception(function() use ($restored) {
		$restored->get(2);
	}, CeleryTimeoutException::class, '#timed out after 2 seconds#');

});

Assert::true(
	$backend::$callCounter['TempBackend::getTaskMetaResult'] > $called,
	'Unserialized AsyncResult with a non-ready state still fetches task meta info.'
);

// AsyncResult must not have an empty task ID.
Assert::exception(
	fn() => new AsyncResult('', $backend),
	InvalidArgumentException::class,
	"#empty task ID#",
);

Assert::exception(
	fn() => new AsyncResult('   ', $backend), // Still counts as empty.
	InvalidArgumentException::class,
	"#empty task ID#",
);
