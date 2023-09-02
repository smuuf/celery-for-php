<?php

use Tester\Assert;

use Smuuf\CeleryForPhp\TaskSignature;
use Smuuf\CeleryForPhp\Exc\InvalidArgumentException;
use Smuuf\CeleryForPhp\Messaging\CeleryMessage;
use Smuuf\CeleryForPhp\Messaging\MessageBuilder;
use Smuuf\CeleryForPhp\Serializers\JsonSerializer;

require __DIR__ . '/../bootstrap.php';

$messageBuilder = new MessageBuilder(new JsonSerializer());

$sig = (new TaskSignature('celery_for_php.tasks.tests.some_task_name'))
	->setQueue('some_very_queue')
	->setCountdown(120)
	->setExpiration('+2 hours')
	->setArgs([1,2,3])
	->setKwargs(['arg_1' => 'hello', 'arg_2' => 'there'])
	->setRetries(10)
	->setTimeLimit(null, 3600);

$taskId = 'abcd_random_id';

// Message/Task protocol version 1.
$message = $messageBuilder->buildTaskMessage(1, $taskId, $sig);
Assert::type(CeleryMessage::class, $message);

// Message/Task protocol version 2.
$message = $messageBuilder->buildTaskMessage(2, $taskId, $sig);
Assert::type(CeleryMessage::class, $message);

Assert::exception(
	fn() => $messageBuilder->buildTaskMessage(99987654, $taskId, $sig),
	InvalidArgumentException::class,
	'Cannot format task message as unknown version 99987654',
);

