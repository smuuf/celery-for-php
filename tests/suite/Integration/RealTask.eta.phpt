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

function test_task_with_eta($eta, int $wait): void {

	global $ts, $c;

	$numA = random_int(1, 100);
	$numB = random_int(1, 100);

	$ts = $ts
		->setArgs([$numA, $numB])
		->setEta($eta);

	$asyncResult = $c->sendTask($ts);
	Assert::type(AsyncResult::class, $asyncResult);
	Assert::same(State::PENDING, $asyncResult->getState(), "Task was sent with ETA, so it's PENDING");
	Assert::false($asyncResult->isReady(), "Task was sent with ETA, so it's not ready");

	sleep($wait);

	Assert::same(State::SUCCESS, $asyncResult->getState(), "ETA passed and task should be a SUCCESS");
	Assert::true($asyncResult->isReady(), "ETA passed and task should be 'ready'");
	Assert::same($numA + $numB, $asyncResult->getResult(), "The result of the task should be correct");

}

test_task_with_eta('now + 3 seconds', 9);
test_task_with_eta(new \DateTime('now + 2 seconds'), 6);

Assert::exception(function() {
	test_task_with_eta('just some garbage', 3);
}, InvalidArgumentException::class, '#cannot convert#i');

Assert::exception(function() {
	test_task_with_eta(['wtf lol'], 3);
}, \TypeError::class);
