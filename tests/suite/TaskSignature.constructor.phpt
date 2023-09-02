<?php

use Smuuf\CeleryForPhp\Exc\InvalidArgumentException;
use Tester\Assert;

use Smuuf\CeleryForPhp\TaskSignature;

require __DIR__ . '/../bootstrap.php';

$s = new TaskSignature(
	taskName: 'celery_for_php.tasks.tests.some_task_name',
	queue: 'some_freakin_queue',
	args: [1, 'a', 'b', 2],
	kwargs: ['damn' => 'yes'],
	retries: 6,
	countdown: 12,
	expiration: '1 day',
	timeLimit: [5, 10],
);

$nowPlus12Seconds = (new DateTime('now'))
	->add(new DateInterval('PT12S')) // +12 seconds
	->format(\DateTime::ATOM);
$in1Day = (new DateTime('now'))
	->add(new DateInterval('P1D')) // +1 day
	->format(\DateTime::ATOM);

Assert::same('celery_for_php.tasks.tests.some_task_name', $s->getTaskName());
Assert::same('some_freakin_queue', $s->getQueue());
Assert::same([1, 'a', 'b', 2], $s->getArgs());
Assert::same(['damn' => 'yes'], $s->getKwargs());
Assert::same(6, $s->getRetries());
Assert::same($nowPlus12Seconds, $s->getEta());
Assert::same($in1Day, $s->getExpiration());
Assert::same([5, 10], $s->getTimeLimit());

//
// Invalid arguments.
//

Assert::exception(function() {
	new TaskSignature(
		taskName: 'celery_for_php.tasks.tests.some_task_name',
		countdown: 12,
		eta: '1 day',
	);
}, InvalidArgumentException::class, 'Cannot specify ETA and countdown at the same time');

Assert::exception(function() {
	new TaskSignature(
		taskName: 'celery_for_php.tasks.tests.some_task_name',
		countdown: 12,
		eta: '1 day',
	);
}, InvalidArgumentException::class, 'Cannot specify ETA and countdown at the same time');
