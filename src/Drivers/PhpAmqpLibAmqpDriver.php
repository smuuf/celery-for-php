<?php

declare(strict_types=1);

namespace Smuuf\CeleryForPhp\Drivers;

use PhpAmqpLib\Wire\AMQPTable;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Connection\AbstractConnection as PhpAmqpLibAbstractConnection;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;

use Smuuf\CeleryForPhp\StrictObject;

/**
 * AMQP driver backed by php-amqplib.
 */
class PhpAmqpLibAmqpDriver implements IAmqpDriver {

	use StrictObject;

	public function __construct(
		private PhpAmqpLibAbstractConnection $amqp,
	) {}

	/**
	 * Publishes a message to an AMQP queue and/or exchange.
	 *
	 * This method declares an exchange and a queue (if a routing key is provided),
	 * and then publishes a message to the specified exchange. If a routing key is
	 * provided, a 'direct' exchange is declared, and the queue is bound to this
	 * exchange using the routing key. If no routing key is provided, a 'fanout'
	 * exchange is declared.
	 *
	 * The message, along with additional properties and headers, is then published
	 * to the exchange. This method handles both scenarios of having and not having
	 * a routing key.
	 *
	 * @param string $queue The name of the queue to declare and to which the message
	 *                      will be published if a routing key is provided.
	 * @param string $exchange The name of the exchange to declare and to which the
	 *                         message will be published.
	 * @param string $routingKey The routing key for binding the queue to the exchange
	 *                           and for publishing the message. If empty, a 'fanout'
	 *                           exchange is used.
	 * @param string $message The message to be published.
	 * @param array $properties Additional properties for the message, such as content type,
	 *                          delivery mode, etc.
	 * @param array $headers Headers to be included in the message.
	 */
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

	/**
	 * Deletes a specified AMQP exchange.
	 *
	 * Attempts to delete the exchange with the given name. If the exchange does not exist,
	 * which is identified by an AMQPProtocolChannelException with a 404 error code,
	 * the exception is caught and ignored. Any other types of
	 * AMQPProtocolChannelException are re-thrown.
	 *
	 * @param string $exchange The name of the exchange to be deleted.
	 */
	public function deleteExchange(string $exchange): void {
		$ch = $this->amqp->channel();

		$ch->exchange_delete($exchange);

		$ch->close();

	}

	/**
	 * Deletes a specified AMQP queue.
	 *
	 * Attempts to delete the queue with the given name. If the queue does not exist,
	 * which is identified by an AMQPProtocolChannelException with a 404 error code,
	 * the exception is caught and ignored. Any other types of
	 * AMQPProtocolChannelException are re-thrown.
	 *
	 * @param string $queueName The name of the queue to be deleted.
	 */
	public function deleteQueue(string $queueName): void {
		$ch = $this->amqp->channel();

		$ch->queue_delete($queueName);

		$ch->close();

	}

	/**
	 * Purges all messages from the specified queue.
	 *
	 * This method attempts to purge a queue with the given name. If the queue
	 * does not exist (identified by the 404 code), the exception is caught and
	 * ignored. For other types of AMQPProtocolChannelException, the exception
	 * is re-thrown.
	 *
	 * @param string $queueName The name of the queue to be purged.
	 *
	 * @throws \PhpAmqpLib\Exception\AMQPProtocolChannelException If an AMQP protocol error occurs,
	 *         other than a non-existent queue (404 error).
	 */
	public function purgeQueue(string $queueName): void {
		$ch = $this->amqp->channel();

		try {
			$ch->queue_purge($queueName);
		} catch (\PhpAmqpLib\Exception\AMQPProtocolChannelException $e) { // @phpstan-ignore-line
			if ($e->getCode() !== 404) {
				throw $e;
			}
		}

		$ch->close();

	}

	/**
	 * Retrieves the number of messages in a specified AMQP queue.
	 *
	 * This method declares a passive, durable queue with the given name to check its existence
	 * and obtain the current message count. It returns the number of messages currently in the queue.
	 * The method uses a passive declaration to ensure that it does not modify or create the queue.
	 *
	 * @param string $queueName The name of the queue for which the message count is required.
	 *
	 * @return int The number of messages currently in the specified queue.
	 */
	public function queueLength(string $queueName): int {

		$ch = $this->amqp->channel();
		[$queue, $messageCount] = $ch->queue_declare($queueName, false, true, false, false);
		$ch->close();

		return $messageCount;

	}

}
