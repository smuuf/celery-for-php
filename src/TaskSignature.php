<?php

namespace Smuuf\CeleryForPhp;

use Smuuf\CeleryForPhp\Exc\InvalidArgumentException;

/**
 * Immutable Celery task signature.
 */
class TaskSignature {

	use StrictObject;

	/** Queue name. */
	protected string $queue = 'celery';

	/**
	 * Task positional args.
	 *
	 * @var list<mixed>
	 */
	protected array $args = [];

	/**
	 * Task keyword args.
	 *
	 * @var array<string, mixed>
	 */
	protected array $kwargs = [];

	/**
	 * The ETA (estimated time of arrival) lets you set a specific date and
	 * time that is the earliest time at which the task will be executed.
	 *
	 * If specified, Celery ultimately stores the datetime as string inside the
	 * broker in this form: "2020-08-29T08:55:28.848881+04:00".
	 *
	 * See https://docs.celeryproject.org/en/stable/userguide/calling.html#eta-and-countdown
	 */
	protected ?string $eta = null;

	/**
	 * Maximum number of retries before giving up. If set to null, it will
	 * **never** stop retrying.
	 */
	protected ?int $retries = null;

	/** Expiry time (UTC) of the task, if set. */
	protected ?string $expiration = null;

	/**
	 * Tuple of [soft, hard] time limit active for task, if set.
	 *
	 * @var array{?int, ?int}
	 */
	protected array $timeLimit = [null, null];

	/**
	 * @param ?list<mixed> $args
	 * @param ?array<string, mixed> $kwargs
	 * @param array{?int, ?int} $timeLimit
	 */
	public function __construct(
		protected string $taskName,
		?string $queue = null,
		?array $args = null,
		?array $kwargs = null,
		?int $retries = null,
		?int $countdown = null,
		null|string|\DateTimeInterface $eta = null,
		null|string|\DateTimeInterface $expiration = null,
		?array $timeLimit = null,
	) {

		if ($eta !== null && $countdown !== null) {
			throw new InvalidArgumentException("Cannot specify ETA and countdown at the same time");
		}

		$queue !== null && $this->rawSetQueue($queue);
		$args !== null && $this->rawSetArgs($args);
		$kwargs !== null && $this->rawSetKwargs($kwargs);
		$retries !== null && $this->rawSetRetries($retries);
		$countdown !== null && $this->rawSetCountdown($countdown);
		$eta !== null && $this->rawSetEta($eta);
		$expiration !== null && $this->rawSetExpiration($expiration);
		$timeLimit !== null && $this->rawSetTimeLimit($timeLimit[0], $timeLimit[1]);

	}

	public function getTaskName(): string {
		return $this->taskName;
	}

	public function getQueue(): string {
		return $this->queue;
	}

	/**
	 * @return list<mixed>
	 */
	public function getArgs(): array {
		return $this->args;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getKwargs(): array {
		return $this->kwargs;
	}

	public function getRetries(): ?int {
		return $this->retries;
	}

	public function getEta(): ?string {
		return $this->eta;
	}

	public function getExpiration(): ?string {
		return $this->expiration;
	}

	/**
	 * @return array{?int, ?int}
	 */
	public function getTimeLimit(): ?array {
		return $this->timeLimit;
	}

	//
	// Immutable setters.
	//

	public function setQueue(string $queue): self {
		$self = clone $this;
		$self->rawSetQueue($queue);
		return $self;
	}

	/**
	 * @param list<mixed> $args
	 */
	public function setArgs(array $args): self {
		$self = clone $this;
		$self->rawSetArgs($args);
		return $self;
	}

	/**
	 * @param array<string, mixed> $kwargs
	 */
	public function setKwargs(array $kwargs): self {
		$self = clone $this;
		$self->rawSetKwargs($kwargs);
		return $self;
	}

	/**
	 * Set maximum number of retries before giving up.  If set to null, it will
	 * **never** stop retrying.
	 */
	public function setRetries(?int $retries): self {
		$self = clone $this;
		$self->rawSetRetries($retries);
		return $self;
	}

	/**
	 * Countdown is a shortcut to set ETA by seconds into the future.
	 * See method TaskSignature::setEta().
	 */
	public function setCountdown(int $seconds): self {
		$self = clone $this;
		$self->rawSetCountdown($seconds);
		return $self;
	}

	/**
	 * Set ETA (estimated time of arrival) for the task.
	 *
	 * This will set a specific date and time that is the earliest time at
	 * which the task will be executed.
	 *
	 * NOTE: ETA can be in the past. Celery will schedule the task to run
	 * immediately.
	 */
	public function setEta(string|\DateTimeInterface $eta): self {
		$self = clone $this;
		$self->rawSetEta($eta);
		return $self;
	}

	/**
	 * Set expiration time for task.
	 */
	public function setExpiration(string|\DateTimeInterface $time): self {
		$self = clone $this;
		$self->rawSetExpiration($time);
		return $self;
	}

	public function setTimeLimit(?int $soft, ?int $hard): self {
		$self = clone $this;
		$self->rawSetTimeLimit($soft, $hard);
		return $self;
	}

	//
	// Internal mutable setters.
	//

	private function rawSetQueue(string $queue): void {

		if (empty($queue)) {
			throw new InvalidArgumentException("Queue cannot be empty");
		}

		$this->queue = $queue;

	}

	/**
	 * @param list<mixed> $args
	 */
	private function rawSetArgs(array $args): void {

		if (!array_is_list($args)) {
			throw new InvalidArgumentException(
				"Args must be passed as an indexed array",
			);
		}

		$this->args = $args;

	}

	/**
	 * @param array<string, mixed> $kwargs
	 */
	private function rawSetKwargs(array $kwargs): void {
		$this->kwargs = $kwargs;
	}

	/**
	 * Set maximum number of retries before giving up. If set to null, it will
	 * **never** stop retrying.
	 */
	private function rawSetRetries(?int $retries): void {
		$this->retries = $retries;
	}

	/**
	 * Countdown is a shortcut to set ETA by seconds into the future.
	 * See method TaskSignature::setEta().
	 */
	private function rawSetCountdown(int $seconds): void {

		if ($seconds < 0) {
			throw new InvalidArgumentException(sprintf(
				"Cannot set negative countdown (passed %d)",
				$seconds,
			));
		}

		$this->rawSetEta("now +$seconds seconds");

	}

	/**
	 * Set ETA (estimated time of arrival) for the task.
	 *
	 * This will set a specific date and time that is the earliest time at
	 * which the task will be executed.
	 *
	 * NOTE: ETA can be in the past. Celery will schedule the task to run
	 * immediately.
	 */
	private function rawSetEta(string|\DateTimeInterface $eta): void {
		$this->eta = self::ensureAtomDatetime($eta);
	}

	/**
	 * Set expiration time for task.
	 */
	private function rawSetExpiration(string|\DateTimeInterface $time): void {
		$this->expiration = self::ensureAtomDatetime($time);
	}

	private function rawSetTimeLimit(?int $soft, ?int $hard): void {
		$this->timeLimit = [$soft, $hard];
	}

	protected static function ensureAtomDatetime(mixed $time): string {

		if (is_string($time)) {

			if (!strtotime($time)) {
				throw new InvalidArgumentException(sprintf(
					"Cannot convert '%s' to datetime",
					$time,
				));
			}

			$time = new \DateTime($time);

		}

		if ($time instanceof \DateTimeInterface) {
			return $time->format(\DateTime::ATOM);
		}

		throw new InvalidArgumentException(sprintf(
			"Cannot convert '%s' to datetime",
			(string) $time,
		));

	}

}
