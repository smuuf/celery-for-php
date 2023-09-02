<?php

namespace Smuuf\CeleryForPhp\Exc;

class CeleryTaskException extends \RuntimeException implements ICeleryForPhpException {

	/**
	 * @param string $type Exception class name.
	 * @param string $message Exception message.
	 * @param string $module Module where the exception occurred.
	 * @param string $traceback Traceback.
	 */
	public function __construct(
		private string $type,
		protected $message,
		private string $module,
		private ?string $traceback,
	) {
		parent::__construct("{$this->type}: {$this->message}");
	}

	/**
	 * Type of the exception/error that occurred during task's runtime
	 * in some Celery worker app.
	 */
	public function getType(): string {
		return $this->type;
	}

	/**
	 * Name of a module (probably a Python module) in some Celery worker app
	 * where the error occurred.
	 */
	public function getModule(): string {
		return $this->module;
	}

	/**
	 * Traceback of the exception/error that occurred during task's runtime
	 * in some Celery worker app.
	 */
	public function getTraceback(): ?string {
		return $this->traceback;
	}

}
