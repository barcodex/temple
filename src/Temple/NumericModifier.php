<?php

namespace Temple;

class NumericModifier
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
				if (!$value || empty($value)) {
					return '';
				}
				break;
			case 'iffalse':
				if ($value && !empty($value)) {
					return '';
				}
				break;
			case 'ifnull':
				if (!is_null($value)) {
					return '';
				}
				break;
			case 'ifnotnull':
				if (is_null($value)) {
					return '';
				}
				break;
			case "htmlcomment":
				$value = "<!--$value-->";
				break;
			case 'replace':
				$default = lavnn('default', $modifierParams, '');
				$fallback = lavnn('fallback', $modifierParams, '');
				$value = lavnn($fallback, $params, $default);
				break;
			case "checked":
				$value = ($value == '1' ? 'checked' : '');
				break;
			case "fixbool":
				$value = (bool) ($value);
				break;
			case 'round':
				$value = round($value, lavnn('digits', $modifierParams, 0));
				break;
			case 'money':
				$value = round($value, 2);
				break;
			case "currency":
				$currencyField = lavnn('field', $modifierParams, 'currency');
				$roundingPrecision = lavnn('precision', $modifierParams, $_SESSION['currency']['smallest']);
				$baseCurrency = TextProcessor::getTagValue($currencyField, $params);
				$numberOnly = lavnn('onlyNumber', $modifierParams, '') == '' ? false : true;
				$withCommission = lavnn('withCommission', $modifierParams, '') == '' ? false : true;
				if ($baseCurrency == '') {
					$baseCurrency = $_SESSION['baseCurrency'];
				}
				$showCurrency = lavnn('code', $modifierParams, 1);
				$showOriginal = lavnn('original', $modifierParams, 0);
				$targetCurrency = $_SESSION['currency']['code'];
				$templateParams = array(
					'baseAmount' => $value,
					'baseCurrency' => $baseCurrency,
					'targetCurrency' => $targetCurrency
				);
				if ($_SESSION['currency']['code'] == $baseCurrency) {
					if (!$numberOnly) {
						$value = ($showCurrency == 1) ? TextProcessor::doTemplate('core', 'currency', $templateParams) : $value;
					}
				} else {
					$rate = CurrencyRate::getCurrentRate($baseCurrency, $targetCurrency, $withCommission);
					$templateParams['targetAmount'] = NumberUtil::roundCurrency($value * $rate, $roundingPrecision);
					if ($numberOnly) {
						$value = $templateParams['targetAmount'];
					} else {
						$templateName = ($showOriginal == 1) ? 'currency.converted.original' : 'currency.converted';
						$value = ($showCurrency == 1) ? TextProcessor::doTemplate('core', $templateName, $templateParams) : $templateParams['targetAmount'];
					}
				} // TODO different rounding for different currencies, different formatting of currencies, make a Util call for this
			  break;
			case 'date':
				$value = date('d.m.Y H:i:s', $value);
				break;
			case 'thousands':
				// @TODO make it configurable from locale. so far usable only for admin purposes
				$value = number_format($value, 0, '', '.');
				break;
			case 'img':
				$imageInfo = Model::getByID($value, 'image')->getData();
				if (count($imageInfo) > 0) {
					$value = $imageInfo['src'];
				} else {
					$value = 'blank.jpg';
				}
				break;
			case 'imgcopy':
				$imageInfo = Model::getByID($value, 'image')->getData();
				if (count($imageInfo) > 0) {
					$value = TextProcessor::doTemplate('main', 'image.copyright', $imageInfo);
				}
				break;
			case 'checkboxvalue':
				$avalue = $value ? 'checked="checked"' : '';
				break;
			case 'checkbox':
				$template = (lavnn('toggle', $modifierParams, 0) == 1 ? 'toggle' : 'checkbox') . ($value ? '.checked' : '.unchecked');
				$nameParts = explode('.', $name);
				$modifierParams['fieldName'] = array_pop($nameParts);
				$value = TextProcessor::doTemplate('core', $template, $modifierParams);
				break;
			case "fwdt":
			case "fwdtemplate":
				$moduleName = lavnn('module', $modifierParams, '');
				$templateName = lavnn('name', $modifierParams, '');
				$value = TextProcessor::doTemplate($moduleName, $templateName, $params);
				break;
			case 'options':
				$src = lavnn('src', $modifierParams, '');
				$keyField = lavnn('keyField', $modifierParams, 'id');
				$valueField = lavnn('valueField', $modifierParams, 'name');
				$data = ($src == '') ? array() : lavnn($src, $params, array());
				$value = join('', PageHelper::generateOptionsForNumbers($data, $keyField, $valueField, $value));
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
			case 'rating':
				if ($value != '') {
					$ratingParams = array(
						'original' => NumberUtil::roundCurrency($value, '0.5'),
						'rating' => 10 * NumberUtil::roundCurrency($value, '0.5')
					);
					$value = TextProcessor::doTemplate('main', 'rating.rated', $ratingParams);
				} else {
					$value = TextProcessor::doTemplate('main', 'rating.nonrated');
				}
				break;
			case 'i18n':
				$modelName = lavnn('model', $modifierParams, '');
				$fieldName = lavnn('field', $modifierParams, '');
				$keyFieldName = lavnn('keyField', $modifierParams, 'id'); // should not be empty!
				$fallbackFieldName = lavnn('fallback', $modifierParams, '');
				$translation = TranslationHelper::getModelFieldTranslation($_SESSION['language']['code'], $modelName, $value, $fieldName, $keyFieldName);
				$value = (($translation == '') ? $params[$fallbackFieldName] : $translation);
				break;
		}

		// Do recursive call if there are more modifiers in the chain
		return (count($modifierChain) > 0) ? Processor::applyModifier($value, $modifierChain, $params) : $value;
	}
}
