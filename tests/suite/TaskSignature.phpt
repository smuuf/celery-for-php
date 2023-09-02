<?php

use Tester\Assert;

use Smuuf\CeleryForPhp\TaskSignature;
use Smuuf\CeleryForPhp\Exc\InvalidArgumentException;

require __DIR__ . '/../bootstrap.php';

$s = new TaskSignature('celery_for_php.tasks.tests.some_task_name');

//
// Queue cannot be set as empty.
//

Assert::exception(function() use ($s) {
	$s->setQueue('');
}, InvalidArgumentException::class, '#Queue cannot be empty#');

//
// Setting/getting task ETA as countdown in seconds.
//

Assert::null($s->getEta());

$s = $s->setCountdown(0);
Assert::type('string', $s->getEta());

$s = $s->setCountdown(1);
Assert::type('string', $s->getEta());
Assert::truthy(strtotime($s->getEta()));

$s = $s->setCountdown(600);
Assert::type('string', $s->getEta());
Assert::truthy(strtotime($s->getEta()));

Assert::exception(function() use ($s) {
	$s = $s->setCountdown(-1);
}, InvalidArgumentException::class, '#Cannot set negative countdown#');

//
// Setting/getting task ETA.
//

$s = $s->setEta('now +5 seconds');
Assert::type('string', $s->getEta());
Assert::truthy(strtotime($s->getEta()));

$s = $s->setEta('now +5 days');
Assert::type('string', $s->getEta());
Assert::truthy(strtotime($s->getEta()));

$s = $s->setEta('now -1 month');
Assert::type('string', $s->getEta());
Assert::truthy(strtotime($s->getEta()));

Assert::exception(function() use ($s) {
	$s = $s->setEta('oh hell no');
}, InvalidArgumentException::class, '#Cannot convert.*to datetime#');

//
// Setting/getting task positional arguments.
//

Assert::same([], $s->getArgs());
$args = [1, 2, false, true, ['nested' => 'dict'], null];
$s = $s->setArgs($args);
Assert::same($args, $s->getArgs());

//
// Setting/getting task positional arguments by a non-indexed array if forbidden.
//

Assert::exception(function() use ($s) {
	$s = $s->setArgs([1, 2, 'wow_a_key' => false, true, ['nested' => 'dict'], null]);
}, InvalidArgumentException::class, '#Args must be passed as an indexed array#');

//
// Setting/getting task keyword arguments.
//

Assert::same([], $s->getKwargs());
$kwargs = ['param_1' => 'jello!', 'some_bool_param' => false];
$s = $s->setKwArgs($kwargs);
Assert::same($kwargs, $s->getKwargs());
