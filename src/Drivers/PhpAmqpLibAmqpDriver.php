<?php

declare(strict_types=1);

namespace Smuuf\CeleryForPhp\Drivers;

use PhpAmqpLib\Wire\AMQPTable;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Connection\AbstractConnection as PhpAmqpLibAbstractConnection;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;

use Smuuf\CeleryForPhp\StrictObject;

/**
 * AMQP driver backed by php-amqplib.
 */
class PhpAmqpLibAmqpDriver implements IAmqpDriver {

	use StrictObject;

	private ?AMQPChannel $channel = null;

	public function __construct(
		private PhpAmqpLibAbstractConnection $amqp,
	) {}

	public function publish(
		string $queue,
		string $exchange,
		string $routingKey,
		string $message,
		array $properties,
		array $headers,
	): void {

		$ch = $this->amqp->channel();
		if (!empty($routingKey)) {

			$ch->exchange_declare(
				exchange: $exchange,
				type: 'direct',
				passive: false,
				durable: true,
				auto_delete: false,
			);
			$ch->queue_declare(
				queue: $queue,
				passive: false,
				durable: true,
				exclusive: false,
				auto_delete: false,
			);
			$ch->queue_bind($queue, $exchange, $routingKey);

		} else {

			$ch->exchange_declare(
				exchange: $exchange,
				type: 'fanout',
				passive: false,
				durable: false,
				auto_delete: false,
			);

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
		} catch (AMQPProtocolChannelException $e) {
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
		} catch (AMQPProtocolChannelException $e) {
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
		} catch (AMQPProtocolChannelException $e) {
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
