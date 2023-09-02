<?php

namespace Smuuf\CeleryForPhp\Serializers;

use Smuuf\CeleryForPhp\Exc\RuntimeException;
use Smuuf\CeleryForPhp\Interfaces\ISerializer;

class MsgpackSerializer implements ISerializer {

	private callable $encoder;
	private callable $decoder;

	public function __construct() {

		// Support for PHP extension msgpack.org[PHP]
		// See https://github.com/msgpack/msgpack-php
		if (
			extension_loaded('msgpack')
			&& function_exists('msgpack_pack')
			&& function_exists('msgpack_unpack')
		) {
			$this->encoder = fn(mixed $input) => \msgpack_pack($input);
			$this->decoder = fn(mixed $input) => \msgpack_unpack($input);
		}

		// Support for pure-PHP msgpack.php
		// See https://github.com/rybakit/msgpack.php
		if (class_exists(\MessagePack\MessagePack::class)) {
			$this->encoder = fn(mixed $input) => \MessagePack\MessagePack::pack($input);
			$this->decoder = fn(mixed $input) => \MessagePack\MessagePack::unpack($input);
		}

		throw new RuntimeException("MsgpackSerializer could not find any suitable Msgpack implementation");

	}

	public function getContentType(): string {
		return "application/x-msgpack";
	}

	public function encode(mixed $input): string {
		return ($this->encoder)($input);
	}

	public function decode(string $input): array {
		return ($this->decoder)($input);
	}

}
