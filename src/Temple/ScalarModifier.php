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
					$precision = lavnn('digits', $modifierParams, 0);
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
				$wordCount = lavnn('words', $modifierParams, 0);
				$charCount = lavnn('chars', $modifierParams, 0);
				$value = TextUtil::shorten($value, $wordCount, $charCount);
				break;
			case "split":
				$delimiter = lavnn('delimiter', $modifierParams, '');
				$value = explode(TextProcessor::glueDecoder($delimiter), $value);
				break;
			case "includesql":
				$moduleName = lavnn('module', $modifierParams, '');
				$templateName = lavnn('name', $modifierParams, '');
				$value = TextProcessor::doSqlTemplate($moduleName, $templateName, $params);
				break;
			case "ifempty":
				$default = lavnn('default', $modifierParams, '');
				$fallback = lavnn('fallback', $modifierParams, '');
				if ($value != '') {
					return $value;
				} elseif ($fallback != '') { // @TODO fallback is now on top level of $params. make it findable in the tree
					return lavnn($fallback, $params, $default);
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
				$default = lavnn('default', $modifierParams, '');
				$fallback = lavnn('fallback', $modifierParams, '');
				$value = lavnn($fallback, $params, $default);
				break;
			case "fwdt":
			case "fwdtemplate":
				$moduleName = lavnn('module', $modifierParams, '');
				$templateName = lavnn('name', $modifierParams, '');
				$value = TextProcessor::doTemplate($moduleName, $templateName, $params);
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
				$value = TextProcessor::urlName($value);
				break;
			case 'rsstime':
				$value = date('D, d M Y H:i:s O', strtotime($value));
				break;
			case 'dbdate':
				// Convert date from current locale format to DB-suitable format
				if ($value != '') {
					$value = DateTimeUtil::DateTimeToDB($value, lavnn('time', $modifierParams, 'now'));
				}
				break;
			case 'date':
				// Convert database-formatted datetime into user favourite locale, without time
				if ($value != '') {
                    $utcDate = date_create_from_format('Y-m-d H:i:s', $value);
                    if (!$utcDate) {
                        $value = date($_SESSION['formats']['date_php'], strtotime($value));
                        Runtime::getInstance()->logWarning('Could not create $utcDate from $value = ' . $value);
                    } else {
                        $offset = lavnn('timezoneOffset', $_SESSION, 0);
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
                        Runtime::getInstance()->logWarning('Could not create $utcDate from $value = ' . $value);
                    } else {
						$offset = lavnn('timezoneOffset', $_SESSION, 0);
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
                        Runtime::getInstance()->logWarning('Could not create $utcDate from $value = ' . $value);
                    } else {
						$offset = lavnn('timezoneOffset', $_SESSION, 0);
                        $localDate = $utcDate->modify("+$offset minutes");
                        $value = $localDate->format($_SESSION['formats']['datetime_php']);
                    }
				}
				break;
			case 'timestamp':
				// @TODO: to AZ: is that code right to get the timestamp of a date?
				if ($value != '') {
					$value = strtotime($value);
				}
				break;
			case "fixurl":
				//@TODO more intelligent algorithm, please
				$value = ($value == '') ? '#' : (substr($value, 0, 4) != 'http' ? 'http://' : '') . $value;
				break;
			case "setlang":
				$newLang = lavnn('lang', $modifierParams, 'en'); // TODO use default language from config
				if (array_key_exists('langvar', $modifierParams)) {
					$newLang = lavnn($modifierParams['langvar'], $params, $newLang);
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
			case 'options':
				$src = lavnn('src', $modifierParams, '');
				$keyField = lavnn('keyField', $modifierParams, 'id');
				$valueField = lavnn('valueField', $modifierParams, 'name');
				$data = ($src == '') ? array() : lavnn($src, $params, array());
				$value = join('', PageHelper::generateOptions($data, $keyField, $valueField, $value));
				break;
			case 'dictvalue':
				$moduleName = lavnn('module', $modifierParams, '');
				$templateName = lavnn('name', $modifierParams, '');
				$data = Framework::getInstance()->getDictionary($moduleName, $templateName, $_SESSION['language']['code']);
				$value = lavnn($value, $data, $value);
				break;
			case 'dictoptions':
				$moduleName = lavnn('module', $modifierParams, '');
				$templateName = lavnn('name', $modifierParams, '');
				$data = Framework::getInstance()->getDictionary($moduleName, $templateName, $_SESSION['language']['code']);
				$value = join('', PageHelper::generateDictionaryOptions(dict2arr($data, $value)));
				break;
			case 'langoptions':
				$scope = lavnn('scope', $modifierParams, 'site');
				$data = Runtime::getInstance()->getLanguages($scope);
				$value = join('', PageHelper::generateOptions($data, 'code', 'name', $value));
				break;
			case 'currencyoptions':
				$scope = lavnn('scope', $modifierParams, 'site');
				$data = Runtime::getInstance()->getCurrencies('site');
				$value = join('', PageHelper::generateOptions($data, 'code', 'name', $value));
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
				$size = lavnn('size', $modifierParams, 50);
				//$default = "http://placekitten.com/300/300"; //@TODO get this from users module config
				$email = md5(strtolower(trim($value)));
				$value = "http://www.gravatar.com/avatar/$email?s=" . $size;
				break;
			case 'rating':
				if ($value != '') {
					$ratingParams = array(
						'original' => NumberUtil::roundCurrency(floatval($value), '0.5'),
						'rating' => 10 * NumberUtil::roundCurrency(floatval($value), '0.5')
					);
					$value = TextProcessor::doTemplate('main', 'rating.rated', $ratingParams);
				} else {
					$value = TextProcessor::doTemplate('main', 'rating.nonrated');
				}
				break;
			case 'translate':
				try {
					$i18n = json_decode($value, true);
					$fieldName = lavnn('field', $modifierParams, '');
					$fallbackFieldName = lavnn('fallback', $modifierParams, '');
					$sessionLang = $_SESSION['language']['code'];
					$translation = ($fallbackFieldName == '') ? '' : lavnn($fallbackFieldName, $params, '');
					if (is_array($i18n) && count($i18n) > 0 && isset($i18n[$sessionLang]) && isset($i18n[$sessionLang][$fieldName])) {
						$translation = lavnn($fieldName, $i18n[$sessionLang], $translation);
					}
					$value = $translation;
				} catch(\Exception $ex) {
					return '';
				}
				break;
			case 'i18n':
				$modelName = lavnn('model', $modifierParams, '');
				$fieldName = lavnn('field', $modifierParams, '');
				$keyFieldName = lavnn('keyField', $modifierParams, 'id');
				$fallbackFieldName = lavnn('fallback', $modifierParams, '');
				$translation = TranslationHelper::getModelFieldTranslation($_SESSION['language']['code'], $modelName, $value, $fieldName, $keyFieldName);
				$value = ($translation == '') ? $params[$fallbackFieldName] : $translation;
				break;
			case 'mailstyle':
				$value = TextProcessor::injectMailStyles($value);
				break;
		}

		// Do recursive call if there are more modifiers in the chain
		return (count($modifierChain) > 0) ? Processor::applyModifier($value, $modifierChain, $params) : $value;
	}
}
