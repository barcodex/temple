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
				$default = lavnn('default', $modifierParams, '');
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
				$fieldName = lavnn('name', $modifierParams, '');
				$value = lavnn($fieldName, $value, '');
				break;
			case "buildquerystring":
				$value = '?' . http_build_query($value);
				break;
			case "cutcolumn":
				$columnName = lavnn('column', $params, '');
				$output = array();
				foreach ($value as $row) {
					$columnValue = lavnn($columnName, $row, '');
					if ($columnValue != '') {
						$output[$columnValue] = $columnValue;
					}
				}
				$value = $output;
				break;
			case "join":
				$glue = lavnn('glue', $modifierParams, '');
				$value = join(TextProcessor::glueDecoder($glue), $value);
				break;
			case "joincolumn":
				$fieldName = lavnn('field', $modifierParams, '');
				$glue = lavnn('glue', $modifierParams, '');
				$rowValues = array();
				foreach ($value as $row) {
					$fieldValue = $row[$fieldName] or '';
					if ($fieldValue != '') {
						$rowValues[] = $row[$fieldName];
					}
				}
				$value = join(TextProcessor::glueDecoder($glue), $rowValues);
				break;
			case "htmlcomment":
				$value = '<!--'.print_r($value, 1).'-->';
				break;
			case "fwdt":
			case "fwdtemplate":
				$moduleName = lavnn('module', $modifierParams, '');
				$templateName = lavnn('name', $modifierParams, '');
				$value = TextProcessor::doTemplate($moduleName, $templateName, $params);
				break;
			case "dot":
			case "dotemplate":
				$moduleName = lavnn('module', $modifierParams, '');
				$templateName = lavnn('name', $modifierParams, '');
                if ($moduleName != '' && $templateName != '') {
                    $value = TextProcessor::doTemplate($moduleName, $templateName, $value);
                } else {
                    $value = '';
                }
				break;
			case "rowtemplate":
			case "looptemplate":
			case "loopt":
				$moduleName = lavnn('module', $modifierParams, '');
				$templateName = lavnn('name', $modifierParams, '');
				$headerTemplateName = lavnn('header', $modifierParams, '');
				$noDataTemplateName = lavnn('nodata', $modifierParams, '');
				if (count($value) > 0) {
					$header = ($headerTemplateName != '') ? TextProcessor::doTemplate($moduleName, $headerTemplateName) : '';
					$value = $header . TextProcessor::loopTemplate($moduleName, $templateName, $value);
				} else {
                    if ($noDataTemplateName != '') {
                        $value = TextProcessor::doTemplate($moduleName, $noDataTemplateName);
                    } else {
						$value = '';
					}
				}
				break;
			case 'emptyt':
				if (count($value) == 0) {
					$moduleName = lavnn('module', $modifierParams, '');
					$templateName = lavnn('name', $modifierParams, '');
					$value = TextProcessor::doTemplate($moduleName, $templateName, $params);
				}
				break;
			case 'replace':
				$default = lavnn('default', $modifierParams, '');
				$fallback = lavnn('fallback', $modifierParams, '');
				$value = lavnn($fallback, $params, $default);
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
			case 'options':
				$keyField = lavnn('keyField', $modifierParams, 'id');
				$valueField = lavnn('valueField', $modifierParams, 'name');
				$value = join('', PageHelper::generateOptions($value, $keyField, $valueField));
				break;
		}

		// Do recursive call if there are more modifiers in the chain
		return (count($modifierChain) > 0) ? Processor::applyModifier($value, $modifierChain, $params) : $value;
	}
}
