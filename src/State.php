<?php

namespace Smuuf\CeleryForPhp;

/**
 * Possible states of a Celery task.
 */
abstract class State {

	/** @var string Task state is unknown (assumed pending since ID is known). */
	public const PENDING = 'PENDING';

	/** @var string Task was received by a worker (only used in events). */
	public const RECEIVED = 'RECEIVED';

	/** @var string Task was started by a worker. */
	public const STARTED = 'STARTED';

	/** @var string Task succeeded */
	public const SUCCESS = 'SUCCESS';

	/** @var string Task failed */
	public const FAILURE = 'FAILURE';

	/** @var string Task was revoked. */
	public const REVOKED = 'REVOKED';

	/** @var string Task was rejected (only used in events). */
	public const REJECTED = 'REJECTED';

	/** @var string Task is waiting for retry. */
	public const RETRY = 'RETRY';

	/** @var list<string> */
	public const READY_STATES = [
		self::SUCCESS,
		self::FAILURE,
		self::REVOKED,
	];

	/** @var list<string> */
	public const UNREADY_STATES = [
		self::PENDING,
		self::RECEIVED,
		self::STARTED,
		self::REJECTED,
		self::RETRY,
	];

	/** @var list<string> */
	public const EXCEPTION_STATES = [
		self::RETRY,
		self::FAILURE,
		self::REVOKED,
	];

	/** @var list<string> */
	public const PROPAGATE_STATES = [
		self::FAILURE,
		self::REVOKED,
	];

}
