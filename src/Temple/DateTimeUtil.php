<?php

namespace Temple;

class DateTimeUtil
{
	public static function validateDateTime($dateTime)
	{
		$date = \DateTime::createFromFormat($_SESSION['formats']['datetime_php'], $dateTime);

		return !$date ? false : true;
	}

	public static function validateDate($date)
	{
		$date = \DateTime::createFromFormat($_SESSION['formats']['date_php'], $date);

		return !$date ? false : true;
	}

	public static function DateToDB($date)
	{
		$date = \DateTime::createFromFormat($_SESSION['formats']['date_php'], $date);
		if (!$date) {
			$date = new \DateTime(); // defaults to 'now' if given string is empty or invalid
		}

		return self::fixDate($date);
	}

	public static function DateTimeToDB($dateTime, $timeOption = 'now')
	{
		$date = \DateTime::createFromFormat($_SESSION['formats']['datetime_php'], $dateTime);
		if (!$date) {
			$date = new \DateTime(); // defaults to 'now' if given string is empty or invalid
		}

		return self::fixTime($date, $timeOption);
	}

	public static function now()
	{
		return new \DateTime();
	}

	public static function fixDate(\DateTime $date = null)
	{
		return self::fixTime($date, '');
	}

	public static function fixTime(\DateTime $date = null, $timeOption = 'now')
	{
		if ($date == null) {
			$date = new \DateTime();
		}
		switch($timeOption) {
			case 'date':
			case 'none':
			case '':
				$dateTime = $date->format('Y-m-d');
				break;
			case 'first':
				$dateTime = $date->format('Y-m-d 00:00:00');
				break;
			case 'last':
				$dateTime = $date->format('Y-m-d ').'23:59:59';
				break;
			case 'now':
			default:
				$dateTime = $date->format('Y-m-d H:i:s');
				break;
		}

		return $dateTime;
	}

	/**
	 * Calculates date interval from $dateTime (given in current format) to current datetime
	 *
	 * @param string $format
	 * @param string $dateTime
	 *
	 * @return \DateInterval
	 */
	public static function calculateDistance($dateTime, $format = null)
	{
		if ($dateTime == '') {
			return array();
		}

		if ($format == null) {
			$format = $_SESSION['formats']['datetime_php'];
		}
		$date = \DateTime::createFromFormat($format, $dateTime, timezone_open('UTC'));
		$now = new \DateTime('now', timezone_open('UTC'));

		$interval = (array) ($date->diff($now));
		$interval['expired'] = ($interval['invert'] == 0) ? 1 : 0;

		return $interval;
	}

}
