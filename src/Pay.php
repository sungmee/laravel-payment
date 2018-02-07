<?php

namespace Sungmee\LaraPay;

class Pay
{
    public function __construct()
    {
		$gateway = config('payment.gateway');
		$gateway = "Gateways\\$gateway";
		return new $gateway;
    }

	public function __call($name, $args) {
        $name = studly_case($name);
        $name = "Gateways\\$name\\$name";
        return new $name($args[0]);
    }
}