<?php

declare(strict_types=1);

namespace Smuuf\CeleryForPhp;

use Smuuf\CeleryForPhp\Exc\RuntimeException;
use Smuuf\CeleryForPhp\Exc\CeleryTaskException;
use Smuuf\CeleryForPhp\Exc\CeleryTimeoutException;
use Smuuf\CeleryForPhp\Exc\InvalidArgumentException;
use Smuuf\CeleryForPhp\Helpers\Functions;
use Smuuf\CeleryForPhp\Helpers\Signals;
use Smuuf\CeleryForPhp\Interfaces\IAsyncResult;
use Smuuf\CeleryForPhp\Interfaces\IResultBackend;

class AsyncResult implements IAsyncResult {

	use StrictObject;

	/** Task meta information. */
	private ?TaskMetaResult $meta = null;

	/**
	 * @param string $taskId Celery task ID.
	 * @param IResultBackend $backend Result backend for getting task results..
	 * @param null|string $taskName (optional) Celery task name for better error
	 *     messages.
	 */
	public function __construct(
		private string $taskId,
		private IResultBackend $backend,
		private ?Control $control = null,
		private ?string $taskName = null,
	) {
		if (empty(trim($this->taskId))) {
			throw new InvalidArgumentException(
				"Cannot create AsyncResult with empty task ID",
			);
		}
	}

	public function getTaskId(): string {
		return $this->taskId;
	}

	public function getState(): string {
		return $this->getTaskMetaResult()->getState();
	}

	public function isReady(): bool {
		return in_array($this->getState(), State::READY_STATES, true);
	}

	public function isSuccessful(): bool {
		return $this->getState() === State::SUCCESS;
	}

	public function isFailed(): bool {
		return $this->getState() === State::FAILURE;
	}

	public function forget(): void {
		$this->backend->forgetResult($this->taskId);
	}

	public function revoke(
		bool $terminate = false,
		string $signal = Signals::SIGTERM,
	): void {

		if (!$this->control) {
			throw new RuntimeException("Control not attached");
		}

		$this->control->revoke(
			taskId: $this->taskId,
			terminate: $terminate,
			signal: $signal,
		);

	}

	public function getTraceback(): ?string {
		return $this->getTaskMetaResult()->getTraceback();
	}

	/**
	 * Returns task's _current_ result. Regardless of its state.
	 *
	 * The result may be empty if the task is not finished yet. Or it can
	 * contain some "temporary result" provided by the task itself, even
	 * if it's still in running.
	 *
	 * If the underlying task ended with an exception, it will be revealed
	 * as (wrapped in) celery-for-php's CeleryTaskException exception.
	 */
	public function getResult(): mixed {

		$result = $this->getTaskMetaResult()->getResult();
		$this->maybeThrow($result);

		return $result;

	}

	/**
	 * Wait until task is ready, and return its final result.
	 */
	public function get(?float $timeout = 10, float $interval = 0.4): mixed {

		$startTime = Functions::monotonicTime();
		$timeoutTime = $timeout !== null
			? $startTime + $timeout
			: null;

		$uInterval = (int) ($interval * 1_000_000);
		while (!$this->isReady()) {

			usleep($uInterval);

			if (
				$timeoutTime !== null
				&& Functions::monotonicTime() > $timeoutTime
			) {

				$identifier = $this->taskName
					? "'$this->taskName' ($this->taskId)"
					: "($this->taskId)";

				throw new CeleryTimeoutException(sprintf(
					"Task %s timed out after %d seconds",
					$identifier,
					$timeout,
				));

			}

		}

		return $this->getResult();

	}

	private function getTaskMetaResult(): TaskMetaResult {

		if (
			!$this->meta
			|| !in_array($this->meta->getState(), State::READY_STATES, true)
		) {

			// Either we have no meta info, or we have meta info from when the
			// task was not ready yet, so let's fetch meta information again.
			$this->meta = $this->backend->getTaskMetaResult($this->taskId);

		}

		return $this->meta;

	}

	private function maybeThrow(mixed $result): void {

		// If the result state is not in any of the known exception states,
		// we surely won't throw anything.
		if (!in_array($this->getState(), State::EXCEPTION_STATES, true)) {
			return;
		}

		if (!isset($result['exc_type'], $result['exc_message'])) {
			return;
		}

		$excType = $result['exc_type'];
		$excMessage = $result['exc_message'];
		$excModule = $result['exc_module'] ?? '<unknown>';
		$tb = $this->getTraceback();

		// Exception message may be a list of strings. Make it into string.
		if (is_array($excMessage)) {
			$excMessage = implode("\n", $excMessage);
		}

		throw new CeleryTaskException($excType, $excMessage, $excModule, $tb);

	}

}
