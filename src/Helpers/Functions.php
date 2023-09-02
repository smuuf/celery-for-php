<?php

declare(strict_types=1);

namespace Smuuf\CeleryForPhp\Helpers;

abstract class Functions {

	/**
	 * Get monotonic time.
	 */
	public static function monotonicTime(): float {
		return hrtime(true) / 1e9; // Nanoseconds into seconds.
	}

	/**
	 * Build the 'origin_id' of Celery task messages the similar way as
	 * Python Celery does.
	 *
	 * See `celery/utils/nodenames.py` in Celery library.
	 */
	public static function getNodeName(): string {
		static $result = null;
		return $result ??= sprintf("c4p:%d@%s", getmypid(), gethostname());
	}

}
