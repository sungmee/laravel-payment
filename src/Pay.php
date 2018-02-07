<?php

namespace Sungmee\LaraPay;

class Pay
{
    public function __construct()
    {
		$platform = config('payment.platform');
		$platform = "Platforms\\$platform";
		return new $platform;
    }

	public function __call($name, $args) {
        $name = studly_case($name);
        $name = "Platforms\\$name\\$name";
        return new $name($args[0]);
    }
}