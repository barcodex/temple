<?php

namespace Temple;

class NumberUtil
{
	public static function roundCurrency($amount, $roundingPrecision)
	{
		$value = $amount;
		switch ($roundingPrecision) {
			case '0.01':
				$value = sprintf("%.2f", $amount);
				break;
			case '0.05':
				$value = sprintf("%.2f", (round($amount * 20) / 20));
				break;
			case '0.1':
				$value = sprintf("%.1f", $amount);
				break;
			case '0.5':
				$value = sprintf("%.1f", (round($amount * 2) / 2));
				break;
			case '1':
				$value = round($amount);
				break;
		}

		return $value;
	}
}
