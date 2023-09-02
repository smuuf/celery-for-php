<?php

namespace Smuuf\CeleryForPhp\Exc;

class NotImplementedException extends \LogicException implements ICeleryForPhpException {

	public function __construct() {
		parent::__construct('Not implemented');
	}

}
