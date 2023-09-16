<?php

use Tester\Assert;

use Smuuf\CeleryForPhp\TaskSignature;
use Smuuf\CeleryForPhp\Exc\CeleryTaskException;
use Smuuf\CeleryForPhp\Exc\InvalidArgumentException;

require __DIR__ . '/../../bootstrap.php';

$c = CeleryFactory::getCelery();

$base = new TaskSignature('main.just_wait');
$ts = $base->setArgs([1, 2, 3, 4]);

$asyncResult = $c->sendTask($ts);
Assert::exception(
	fn() => $asyncResult->get(),
	CeleryTaskException::class,
	'#just_wait.*takes from 1 to 2 positional arguments but 4 were given#',
);

$ts = $base->setKwargs(['vole' => 1]);
$asyncResult = $c->sendTask($ts);
Assert::exception(
	fn() => $asyncResult->get(),
	CeleryTaskException::class,
	'#just_wait.*got an unexpected keyword argument.*vole#',
);

Assert::exception(
	fn() => $base->setArgs(['this' => 'is_not_indexed']),
	InvalidArgumentException::class,
	'#Args must be passed as an indexed array#',
);
