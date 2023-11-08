<?php

/**
 * @dataprovider testMatrixBuilder.php
 */

use Tester\Assert;

use Smuuf\CeleryForPhp\State;
use Smuuf\CeleryForPhp\TaskSignature;

require __DIR__ . '/../../bootstrap.php';

$testArgs = \Tester\Environment::loadData();
$c = TestCeleryFactory::getCelery($testArgs);

// Call real-life Python Celery's task.
$ts = (new TaskSignature('main.zip_dicts'))
	->setQueue(TestCeleryFactory::buildTestQueueName($testArgs))
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
