<?php

/**
 * @dataprovider testMatrixBuilder.php
 */

use Tester\Assert;

use Smuuf\CeleryForPhp\State;
use Smuuf\CeleryForPhp\TaskSignature;
use Smuuf\CeleryForPhp\Exc\CeleryTimeoutException;

require __DIR__ . '/../../bootstrap.php';

$testArgs = \Tester\Environment::loadData();
$c = TestCeleryFactory::getCelery($testArgs);

// Call real-life Python Celery's task.
$ts = (new TaskSignature('main.just_wait'))
	->setQueue(TestCeleryFactory::buildTestQueueName($testArgs))
	->setArgs([1]);

$asyncResult = $c->sendTask($ts);
$asyncResult->get(); // Wait for result.
Assert::same(State::SUCCESS, $asyncResult->getState(), "We waited and now the state is a SUCCESS");
Assert::same(null, $asyncResult->getResult(), "Task returned expected result");

// Call real-life Python Celery's task.
$ts = (new TaskSignature('main.just_wait'))
	->setQueue(TestCeleryFactory::buildTestQueueName($testArgs))
	->setArgs([5, "my expected result"]);

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
