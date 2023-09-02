<?php

use Smuuf\CeleryForPhp\StrictObject;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$o = new class {

	use StrictObject;

	public $propPublic = 1;
	protected $propProtected = 2;
	private $propPrivate = 3;

};

Assert::same(1, $o->propPublic);
Assert::exception(fn() => $o->propProtected, \LogicException::class, '#Cannot read#');
Assert::exception(fn() => $o->propPrivate, \LogicException::class, '#Cannot read#');
Assert::exception(fn() => $o->propNonexistent, \LogicException::class, '#Cannot read#');

$o->propPublic = 111;
Assert::same(111, $o->propPublic);
Assert::exception(fn() => $o->propProtected = 222, \LogicException::class, '#Cannot write#');
Assert::exception(fn() => $o->propPrivate = 333, \LogicException::class, '#Cannot write#');
Assert::exception(fn() => $o->propNonexistent = 0, \LogicException::class, '#Cannot write#');
