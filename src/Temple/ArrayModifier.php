<?php

namespace Temple;

class ArrayModifier
	extends Modifier
{
	/** @inheritdoc */
	public static function apply(
		$value,
		$modifierChain,
		$params = array()
	) {
		// Check if variable is an object
		if (is_object($value)) {
			return 'Object';
		}

		// Check if variable is not an array
		if (!is_array($value)) {
			return $value;
		}

		// Fetch the modifier to apply on the value
		$nextModifier = array_shift($modifierChain);

		// Check if modifier is valid
		if ($nextModifier == '' && count($modifierChain) == 0) {
			return $value;
		}
		// Parse modifier parameter string
		list($modifierName, $modifierParams) = self::parseModifierParameterString($nextModifier);

		// Apply filter on the value
		switch ($modifierName) {
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
			case "ifnotempty":
			case "stopifempty":
				if (count($value) == 0) {
					return "";
				}
				break;
			case "ifempty":
			case "stopifnotempty":
				$default = Util::lavnn('default', $modifierParams, '');
				if (count($value) != 0) {
					return "";
				} elseif ($default != '') {
					return $default;
				}
				break;
			case "length":
				$value = count($value);
				break;
			case "first":
				$value = $value[0];
				break;
			case "last":
				$value = $value[count($value)];
				break;
			case "field":
				$fieldName = Util::lavnn('name', $modifierParams, '');
				$value = Util::lavnn($fieldName, $value, '');
				break;
			case "buildquerystring":
				$value = '?' . http_build_query($value);
				break;
			case "cutcolumn":
				$columnName = Util::lavnn('column', $params, '');
				$output = array();
				foreach ($value as $row) {
					$columnValue = Util::lavnn($columnName, $row, '');
					if ($columnValue != '') {
						$output[$columnValue] = $columnValue;
					}
				}
				$value = $output;
				break;
			case "join":
				$glue = Util::lavnn('glue', $modifierParams, '');
				$value = join(Processor::glueDecoder($glue), $value);
				break;
			case "joincolumn":
				$fieldName = Util::lavnn('field', $modifierParams, '');
				$glue = Util::lavnn('glue', $modifierParams, '');
				$rowValues = array();
				foreach ($value as $row) {
					$fieldValue = $row[$fieldName] or '';
					if ($fieldValue != '') {
						$rowValues[] = $row[$fieldName];
					}
				}
				$value = join(Processor::glueDecoder($glue), $rowValues);
				break;
			case "htmlcomment":
				$value = '<!--'.print_r($value, 1).'-->';
				break;
			case 'replace':
				$default = Util::lavnn('default', $modifierParams, '');
				$fallback = Util::lavnn('fallback', $modifierParams, '');
				$value = Util::lavnn($fallback, $params, $default);
				break;
			case "ksort":
				ksort($value);
				break;
			case "asort":
				asort($value);
				break;
			case "sort":
				sort($value);
				break;
			case "dump":
				$value = print_r($value, true);
				break;
			case "json":
				$value = json_encode($value);
				break;
		}

		// Do recursive call if there are more modifiers in the chain
		return (count($modifierChain) > 0) ? Processor::applyModifier($value, $modifierChain, $params) : $value;
	}
}
