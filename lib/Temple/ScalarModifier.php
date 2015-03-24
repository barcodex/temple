<?php

namespace Temple;

class ScalarModifier
	extends Modifier
{
	/** @inheritdoc */
	public static function apply(
		$value,
		$modifierChain,
		$params = array()
	) {
		// Check if variable is of array type
		if (is_array($value)) {
			return 'Array';
		}

		// Check if variable is an object
		if (is_object($value)) {
			return 'Object';
		}

		// Fetch the modifier to apply on the value
		$nextModifier = array_shift($modifierChain);

		// Check if modifier is valid
		if ($nextModifier == '' && count($modifierChain) == 0) {
			return $value;
		}

		// Parse modifier parameter string
		list($modifierName, $modifierParams) = self::parseModifierParameterString($nextModifier);

		// Apply modifier on the value
		switch ($modifierName) {
			case 'iftrue':
				$value = (bool) $value;
				if (!$value || empty($value) || $value == 0) {
					return '';
				}
				break;
			case 'iffalse':
				$value = (bool) $value;
				if ($value && !empty($value)) {
					return '';
				}
				break;
			case 'ifnull':
				if (!is_null($value) && !empty($value)) {
					return '';
				}
				break;
			case 'ifnotnull':
				if (is_null($value) || empty($value)) {
					return '';
				}
				break;
			case 'tag':
				return '{{'.$value.'}}';
				break;
			case "lowercase":
				$value = strtolower($value);
				break;
			case "uppercase":
				$value = strtoupper($value);
				break;
			case "trim":
				$value = trim($value);
				break;
			case "length":
				$value = strlen($value);
				break;
			case "wordcount":
				//$value = str_word_count($value, 0, '/[\p{C}\p{S}\p{Z}]+/'); // does not work with utf-8
				$value = str_replace("\xC2\xAD",'', $value); // soft hyphen encoded in UTF-8
				$value = preg_match_all('~[\p{L}\'\-]+~u', $value);
				break;
			case "htmlentities":
				$value = htmlentities($value);
				break;
			case "round":
				if (is_numeric($value)) {
					$precision = Util::lavnn('digits', $modifierParams, 0);
					$value = round($value, $precision);
				} else {
					$value = 0;
				}
				break;
			case "zero":
				if ($value == '') {
					$value = 0;
				}
				break;
			case "shortener":
				$wordCount = Util::lavnn('words', $modifierParams, 0);
				$charCount = Util::lavnn('chars', $modifierParams, 0);
				$value = TextUtil::shorten($value, $wordCount, $charCount);
				break;
			case "split":
				$delimiter = Util::lavnn('delimiter', $modifierParams, '');
				$value = explode(Processor::glueDecoder($delimiter), $value);
				break;
			case "ifempty":
				$default = Util::lavnn('default', $modifierParams, '');
				$fallback = Util::lavnn('fallback', $modifierParams, '');
				if ($value != '') {
					return $value;
				} elseif ($fallback != '') { // @TODO fallback is now on top level of $params. make it findable in the tree
					return Util::lavnn($fallback, $params, $default);
				} else {
					return $default;
				}
				break;
			case "ifnotempty":
				if (is_null($value) || $value == '') {
					return '';
				}
				break;
			case 'replace':
				$default = Util::lavnn('default', $modifierParams, '');
				$fallback = Util::lavnn('fallback', $modifierParams, '');
				$value = Util::lavnn($fallback, $params, $default);
				break;
			case "checked":
				$value = ($value == '1' ? 'checked' : '');
				break;
			case "dbsafe":
				$value = TextUtil::dbsafe($value);
				break;
			case "jssafe":
				$value = TextUtil::jssafe($value);
				break;
			case "htmlsafe":
				$value = TextUtil::htmlsafe($value);
				break;
			case "urlencode":
				$value = urlencode($value);
				break;
			case "fixfloat":
				$value = floatval($value);
				break;
			case "fixint":
				$value = intval($value);
				break;
			case "fixbool":
				$value = (bool) ($value);
				break;
			case 'urlname':
				$value = Processor::urlName($value);
				break;
			case 'rsstime':
				$value = date('D, d M Y H:i:s O', strtotime($value));
				break;
			case 'dbdate':
				// Convert date from current locale format to DB-suitable format
				if ($value != '') {
					$value = DateTimeUtil::DateTimeToDB($value, Util::lavnn('time', $modifierParams, 'now'));
				}
				break;
			case 'date':
				// Convert database-formatted datetime into user favourite locale, without time
				if ($value != '') {
                    $utcDate = date_create_from_format('Y-m-d H:i:s', $value);
                    if (!$utcDate) {
                        $value = date($_SESSION['formats']['date_php'], strtotime($value));
                    } else {
                        $offset = Util::lavnn('timezoneOffset', $_SESSION, 0);
                        $localDate = $utcDate->modify("+$offset minutes"); // fixing the timezone offset can change the date
                        $value = $localDate->format($_SESSION['formats']['date_php']);
                    }
				}
				break;
			case 'time':
				// Convert database-formatted datetime into user favourite locale, without date
				if ($value != '') {
					$utcDate = date_create_from_format('Y-m-d H:i:s', $value);
                    if (!$utcDate) {
                        $value = date($_SESSION['formats']['time_php'], strtotime($value));
                    } else {
						$offset = Util::lavnn('timezoneOffset', $_SESSION, 0);
                        $localDate = $utcDate->modify("+$offset minutes");
                        $value = $localDate->format($_SESSION['formats']['time_php']);
                    }
				}
				break;
			case 'datetime':
				// Convert database-formatted datetime into user current locale, using time
				if ($value != '') {
					$utcDate = date_create_from_format('Y-m-d H:i:s', $value);
                    if (!$utcDate) {
                        $value = date($_SESSION['formats']['datetime_php'], strtotime($value));
                    } else {
						$offset = Util::lavnn('timezoneOffset', $_SESSION, 0);
                        $localDate = $utcDate->modify("+$offset minutes");
                        $value = $localDate->format($_SESSION['formats']['datetime_php']);
                    }
				}
				break;
			case 'timestamp':
				if ($value != '') {
					$value = strtotime($value);
				}
				break;
			case "fixurl":
				//@TODO more intelligent algorithm, please
				$value = ($value == '') ? '#' : (substr($value, 0, 4) != 'http' ? 'http://' : '') . $value;
				break;
			case "setlang":
				$newLang = Util::lavnn('lang', $modifierParams, 'en');
				if (array_key_exists('langvar', $modifierParams)) {
					$newLang = Util::lavnn($modifierParams['langvar'], $params, $newLang);
				}
				$value = '/'. $newLang . substr($value, 3);
				break;
			case "nohtml":
				$value = strip_tags($value);
				break;
			case "htmlcomment":
				$value = "<!--$value-->";
				break;
			case 'unserialize':
				$value = ($value == '') ? array() : json_decode($value, true);
				break;
			case 'loremipsum':
				$loremIpsum = array(
					"Donec ullamcorper nulla non metus auctor fringilla.",
					"Vestibulum id ligula porta felis euismod semper.",
					"Praesent commodo cursus magna, vel scelerisque nisl consectetur.",
					"Fusce dapibus, tellus ac cursus commodo."
				);
				$value = ($value == '') ? join(' ', $loremIpsum) : $value;
				break;
			case 'gravatar':
				$size = Util::lavnn('size', $modifierParams, 50);
				$email = md5(strtolower(trim($value)));
				$value = "http://www.gravatar.com/avatar/$email?s=" . $size;
				break;
		}

		// Do recursive call if there are more modifiers in the chain
		return (count($modifierChain) > 0) ? Processor::applyModifier($value, $modifierChain, $params) : $value;
	}
}
