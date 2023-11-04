<?php

declare(strict_types=1);

namespace Smuuf\CeleryForPhp;

use Smuuf\CeleryForPhp\Exc\InvalidArgumentException;

/**
 * Object representing metadata of task and its actual result. Celery stores
 * this metadata for existing (executed) tasks into a result backend (e.g.
 * Redis) and we then use our PHP implementation of result backend
 * (`IResultBackend`) to work with it.
 *
 * Equivalent of "task meta" inside Python Celery's `AsyncResult` objects.
 */
class TaskMetaResult {

	use StrictObject;

	private const EXPECTED_FIELDS = [
		'task_id',
		'status', // State is stored under 'status' key by Celery.
		'result',
		'date_done',
		'traceback',
	];

	/**
	 * @param string $taskId Task ID
	 * @param string $state Task state (see State.php)
	 * @param mixed $result Celery task result.
	 * @param ?string $dateDone Datetime string when task was finished.
	 * @param ?string $traceback Task error (Python) traceback as string or null.
	 */
	public function __construct(
		protected string $taskId,
		protected string $state = State::PENDING,
		protected mixed $result = null,
		protected ?string $dateDone = null,
		protected ?string $traceback = null,
	) {}

	/**
	 * Build a new `TaskMetaResult` object from a JSON string.
	 */
	public static function fromJson(
		string $taskId,
		?string $json,
	): TaskMetaResult {

		// If there are no meta data provided (result backend found no data),
		// create a null "pending" state object.
		if (empty($json)) {
			return self::nullObject($taskId);
		}

		return self::fromArray($taskId, json_decode($json, true));

	}

	/**
	 * Build a new `TaskMetaResult` object from an array.
	 */
	public static function fromArray(
		string $taskId,
		?array $data,
	): TaskMetaResult {

		if (empty($data)) {
			return self::nullObject($taskId);
		}

		$missingFields = array_diff(self::EXPECTED_FIELDS, array_keys($data));
		if ($missingFields) {
			throw new InvalidArgumentException(sprintf(
				"Unable to create TaskMetaResult from invalid data. "
				. "Missing fields: " . implode(',', $missingFields),
			));
		}

		return new self(
			$data['task_id'],
			$data['status'], // State is stored under 'status' key by Celery.
			$data['result'],
			$data['date_done'],
			$data['traceback'],
		);

	}

	public static function nullObject(string $taskId) {
		return new self($taskId);
	}

	public function getTaskId(): ?string {
		return $this->taskId;
	}

	public function getState(): string {
		return $this->state;
	}

	public function getResult(): mixed {
		return $this->result;
	}

	public function getTraceback(): ?string {
		return $this->traceback;
	}

	public function getDateDone(): ?string {
		return $this->dateDone;
	}

}
