<?php

namespace Smuuf\CeleryForPhp\Interfaces;

interface IAsyncResult {

	public function getTaskId(): string;
	public function getState(): string;
	public function getResult(): mixed;

	public function isReady(): bool;
	public function isSuccessful(): bool;
	public function isFailed(): bool;

	public function forget(): void;
	public function getTraceback(): ?string;

}
