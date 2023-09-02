<?php

declare(strict_types=1);

namespace Smuuf\CeleryForPhp\Backends;

use Smuuf\CeleryForPhp\TaskMetaResult;
use Smuuf\CeleryForPhp\StrictObject;
use Smuuf\CeleryForPhp\Interfaces\IResultBackend;
use Smuuf\CeleryForPhp\Drivers\IRedisDriver;

class RedisBackend implements IResultBackend {

	use StrictObject;

	/**
	 * Celery task meta Redis key prefix (as specified in Celery source code).
	 */
	public const TASK_KEYPREFIX = 'celery-task-meta-';
	public const GROUP_KEYPREFIX = 'celery-taskset-meta-';

	public function __construct(
		private IRedisDriver $redis,
	) {}

	/**
	 * Return TaskMetaResult object for task with specified ID.
	 * If such task is not present in result backend, a void TaskMetaResult object
	 * with 'PENDING' state will be returned.
	 */
	public function getTaskMetaResult(string $taskId): TaskMetaResult {

		$key = $this->getResultKey($taskId);
		return TaskMetaResult::fromJson(
			$taskId,
			$this->redis->get($key) ?? null,
		);

	}

	/**
	 * Deletes (forgets) task result from result backend.
	 */
	public function forgetResult(string $taskId): void {
		$key = $this->getResultKey($taskId);
		$this->redis->del($key);
	}

	public function storeResult(
		string $taskId,
		mixed $result,
		string $state,
		?string $traceback = null,
	): void {

		// Celery stores date_done in ISO 8601 format which is \DateTime::ATOM
		// and not \DateTime::ISO8601.
		// See https://docs.python.org/3/library/datetime.html#datetime.datetime.isoformat
		// See https://www.php.net/manual/en/class.datetimeinterface.php#datetime.constants.iso8601
		$dateDone = gmdate(\DateTime::ATOM);

		$key = $this->getResultKey($taskId);
		$this->redis->set($key, json_encode([
			'task_id' => $taskId,
			'result' => $result,
			'status' => $state,
			'date_done' => $dateDone,
			'traceback' => $traceback,
		]));

	}

	/**
	 * Build and return string key under which the result for the task should be
	 * stored in Redis.
	 */
	protected function getResultKey(string $taskId): string {
		return self::TASK_KEYPREFIX . $taskId;
	}

}
