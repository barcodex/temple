<?php

namespace Temple;

class TextUtil
{
	public static function shorten($value,  $wordCount = 0, $charCount = 0)
	{
		if ($wordCount > 0) {
			$words = explode(' ', $value);
			if (count($words) > $wordCount) {
				$value = join(' ', array_slice($words, 0, $wordCount));
			}
		} elseif ($charCount > 0) {
			if (strlen($value) > $charCount) {
				$value = substr($value, 0, $charCount);
			}
		}

		return $value;
	}

	public static function htmlSafe($value)
	{
		return str_replace('"', '&quot;', $value);
	}

	public static function jsSafe($value)
	{
		return str_replace("'", "\'", $value);
	}

	public static function dbSafe($value)
	{
		return str_replace("'", "''", $value);
	}

	public static function noCrLf($value)
	{
		return str_replace(array(PHP_EOL), '', $value);
	}

	/**
	 * Cuts off one-line comment from the line (comment is identified by the hash symbol
	 *
	 * @param $line
	 *
	 * @return string
	 */
	public static function cutOffComment($line) {
		$parts = explode('#', trim($line), 2);

		return trim(array_shift($parts));
	}

}
