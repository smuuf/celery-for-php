<?php

namespace Smuuf\CeleryForPhp\Interfaces;

use Smuuf\CeleryForPhp\DeliveryInfo;
use Smuuf\CeleryForPhp\Messaging\CeleryMessage;

interface IBroker {

	public function publish(
		CeleryMessage $msg,
		DeliveryInfo $deliveryInfo,
	);

}
