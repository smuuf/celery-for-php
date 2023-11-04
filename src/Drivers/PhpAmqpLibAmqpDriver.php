<?php

declare(strict_types=1);

namespace Smuuf\CeleryForPhp\Drivers;

use Smuuf\CeleryForPhp\StrictObject;
use PhpAmqpLib\Wire\AMQPTable;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * AMQP driver backed by php-amqplib.
 */
class PhpAmqpLibAmqpDriver implements IAmqpDriver {

	use StrictObject;

	private $message = null;
	private $channel = null;

	public function __construct(
		private $amqp,
	) {}

	public function publish(string $queue, string $exchange, string $routingKey, string $message, array $properties, array $headers): void {
		$ch = $this->amqp->channel();
		if (!empty($routingKey)) {
			$ch->exchange_declare($exchange, 'direct', false, true, false);
			$ch->queue_declare($queue, false, true, false, false);
			$ch->queue_bind($queue, $exchange, $routingKey);
		} else {
			$ch->exchange_declare($exchange, 'fanout', false, true, false);
		}
		$properties['application_headers'] = new AMQPTable($headers);
		$amqpMessage = new AMQPMessage($message, $properties);
		$ch->basic_publish($amqpMessage, $exchange, $routingKey);
		$ch->close();
	}

	public function deleteExchange(string $exchange): void {
		$ch = $this->amqp->channel();

		try {
			$ch->exchange_delete($exchange);
		} catch (\PhpAmqpLib\Exception\AMQPProtocolChannelException $e) {
			if ($e->getCode() !== 404) {  // ignore exchange 404
				throw $e;
			}
		}
		$ch->close();

	}

	public function deleteQueue(string $queueName): void {
		$ch = $this->amqp->channel();

		try {
			$ch->queue_delete($queueName);
		} catch (\PhpAmqpLib\Exception\AMQPProtocolChannelException $e) {
			if ($e->getCode() !== 404) {  // ignore queue 404
				throw $e;
			}
		}
		$ch->close();

	}

	public function purgeQueue(string $queueName): void {
		$ch = $this->amqp->channel();

		try {
			$ch->queue_purge($queueName);
		} catch (\PhpAmqpLib\Exception\AMQPProtocolChannelException $e) {
			if ($e->getCode() !== 404) {  // ignore queue 404
				throw $e;
			}
		}
		$ch->close();

	}

	public function queueLength(string $queueName): int {
		$ch = $this->amqp->channel();

		[$queue, $messageCount] = $ch->queue_declare($queueName, false, true, false, false);

		$ch->close();

		return $messageCount;
	}

}
