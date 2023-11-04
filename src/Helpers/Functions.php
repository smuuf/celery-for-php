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
	 * Build a random UUID v4.
	 * Thanks to https://stackoverflow.com/a/15875555/1285669.
	 */
	public static function uuid4(): string {

		$data = random_bytes(16);

		// Set version to 01006.
		$data[6] = chr(ord($data[6]) & 0x0F | 0x40);
		// Set bits 6-7 to 10.
		$data[8] = chr(ord($data[8]) & 0x3F | 0x80);

		// Output the 36 character UUID.
		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

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
