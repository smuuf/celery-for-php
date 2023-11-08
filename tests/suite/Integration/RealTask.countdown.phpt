<?php

/**
 * @dataprovider testMatrixBuilder.php
 */

use Tester\Assert;

use Smuuf\CeleryForPhp\State;
use Smuuf\CeleryForPhp\AsyncResult;
use Smuuf\CeleryForPhp\TaskSignature;
use Smuuf\CeleryForPhp\Exc\InvalidArgumentException;

require __DIR__ . '/../../bootstrap.php';

$testArgs = \Tester\Environment::loadData();
$c = TestCeleryFactory::getCelery($testArgs);

// Call real-life Python Celery's task.
$ts = (new TaskSignature('main.add'))
	->setQueue(TestCeleryFactory::buildTestQueueName($testArgs));

function test_task_with_countdown(int $countdown): void {

	global $ts, $c;

	$numA = random_int(1, 100);
	$numB = random_int(1, 100);

	$ts = $ts
		->setArgs([$numA, $numB])
		->setCountdown($countdown);

	$asyncResult = $c->sendTask($ts);
	Assert::type(AsyncResult::class, $asyncResult);
	Assert::same(State::PENDING, $asyncResult->getState(), "Task was sent with countdown, so it's PENDING");
	Assert::false($asyncResult->isReady(), "Task was sent with countdown, so it's not ready");

	sleep($countdown + 5);

	Assert::same(State::SUCCESS, $asyncResult->getState(), "Countdown passed and task should be a SUCCESS");
	Assert::true($asyncResult->isReady(), "Countdown passed and task should be 'ready'");
	Assert::same($numA + $numB, $asyncResult->getResult(), "The result of the task should be correct");

}

test_task_with_countdown(5);

Assert::exception(function() {
	test_task_with_countdown(-1);
}, InvalidArgumentException::class, '#cannot.*negative#i');
