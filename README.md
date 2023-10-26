# celery-for-php 🌱

[![PHP tests](https://github.com/smuuf/celery-for-php/actions/workflows/php.yml/badge.svg)](https://github.com/smuuf/celery-for-php/actions/workflows/php.yml)

A modern PHP client library for [Celery - Distributed Task Queue](https://docs.celeryq.dev).

## Requirements
- PHP 8.0+

## Installation

Install [celery-for-php](https://packagist.org/packages/smuuf/celery-for-php) via Composer.

```bash
composer require smuuf/celery-for-php
```

### Redis (Predis)

If you want to use Redis as a broker and/or result backend, celery-for-php contains a Redis driver backed by [`Predis`](https://github.com/predis/predis).

The Predis `Client` object then needs to be wrapped in our `Smuuf\CeleryForPhp\Drivers\PredisRedisDriver` driver object, which provides the necessary interface for celery-for-php's actual communication with Redis.

## Example usage

```php
<?php

use Predis\Client as PredisClient;

use Smuuf\CeleryForPhp\Celery;
use Smuuf\CeleryForPhp\TaskSignature;
use Smuuf\CeleryForPhp\Brokers\RedisBroker;
use Smuuf\CeleryForPhp\Drivers\PredisRedisDriver;
use Smuuf\CeleryForPhp\Backends\RedisBackend;

$predis = new PredisClient(['host' => '127.0.0.1']);
$redisDriver = new PredisRedisDriver($predis);

$celery = new Celery(
	new RedisBroker($redisDriver),
	new RedisBackend($redisDriver),
	// Optionally explicit config object.
	// config: new \Smuuf\CeleryForPhp\Config(...)
);

$task = new TaskSignature(
	taskName: 'my_celery_app.add_numbers',
	queue: 'my_queue', // Optional, 'celery' by default.
	args: [1, 3, 5],
	// kwargs: ['arg_a' => 123, 'arg_b' => 'something'],
	// eta: 'now +10 minutes',
	// ... or more optional arguments.
);

// Send the task into Celery.
$asyncResult = $celery->sendTask($task);

// Wait for the result (up to 10 seconds by default) and return it.
// Alternatively a \Smuuf\CeleryForPhp\Exc\CeleryTimeoutException exception will
// be thrown if the task won't finish in time.
$result = $asyncResult->get();
// $result === 9
```
