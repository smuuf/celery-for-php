# celery-for-php 🌱

[![PHP tests](https://github.com/smuuf/celery-for-php/actions/workflows/php.yml/badge.svg)](https://github.com/smuuf/celery-for-php/actions/workflows/php.yml)

A modern PHP client library for [Celery - Distributed Task Queue](https://docs.celeryq.dev).

## Requirements
- PHP 8.0+

## Example

```php
<?php

use Smuuf\CeleryForPhp\Celery;
use Smuuf\CeleryForPhp\TaskSignature;
use Smuuf\CeleryForPhp\Brokers\RedisBroker;
use Smuuf\CeleryForPhp\Drivers\PredisDriver;
use Smuuf\CeleryForPhp\Backends\RedisBackend;

$predis = new PredisClient(['host' => '127.0.0.1']);
$redisDriver = new PredisDriver($predis);

$celery = new Celery(
	new RedisBroker($redisDriver),
	new RedisBackend($redisDriver),
);

$task = new TaskSignature(
	taskName: 'my_celery_app.add_numbers',
	queue: 'my_queue', // Optional, 'celery' by default.
	args: [1, 3, 5],
	// ... more optional arguments.
);

// Send the task into Celery.
$asyncResult = $celery->sendTask($task);

// Wait for the result (up to 10 seconds by default) and return it.
// Alternatively a \Smuuf\CeleryForPhp\Exc\CeleryTimeoutException exception will
// be thrown if the task won't finish in time.
$result = $asyncResult->get();
// $result === 9
```
