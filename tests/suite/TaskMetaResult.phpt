<?php

use Smuuf\CeleryForPhp\Exc\InvalidArgumentException;
use Tester\Assert;

use Smuuf\CeleryForPhp\State;
use Smuuf\CeleryForPhp\TaskMetaResult as TaskMetaResult;

require __DIR__ . '/../bootstrap.php';

const TASK_ID = 'some_very_task_id_wow';

//
// Create empty task meta info directly.
//

$empties = [
	new TaskMetaResult(TASK_ID),
	TaskMetaResult::fromArray(TASK_ID, []),
	TaskMetaResult::fromArray(TASK_ID, null),
	TaskMetaResult::fromJson(TASK_ID, ''),
	TaskMetaResult::fromArray(TASK_ID, null),
];

foreach ($empties as $tm) {
	Assert::same(TASK_ID, $tm->getTaskId());
	Assert::same(State::PENDING, $tm->getState());
	Assert::null($tm->getResult());
	Assert::null($tm->getDateDone());
	Assert::null($tm->getTraceback());
}

$tm = new TaskMetaResult(
	TASK_ID,
	State::STARTED,
	['pid' => 123456, 'hostname' => 'some_machine']
);
Assert::same(TASK_ID, $tm->getTaskId());
Assert::same(State::STARTED, $tm->getState());
Assert::type('array', $tm->getResult());
Assert::null($tm->getDateDone());
Assert::null($tm->getTraceback());

//
// Invalid data.
//
$data = ['lol' => 1];
Assert::exception(
	fn() => TaskMetaResult::fromArray(TASK_ID, $data),
	InvalidArgumentException::class,
	'#invalid data.*missing fields.*task_id.*status.*result.*date_done.*traceback#i',
);
