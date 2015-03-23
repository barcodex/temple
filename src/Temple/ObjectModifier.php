<?php

namespace Temple;

class ObjectModifier
	extends Modifier
{
	/** @inheritdoc */
	public static function apply(
		$value,
		$modifierChain,
		$params = array()
	) {
		$r = Runtime::getInstance();

		// Check if variable is of array type
		if (is_array($value)) {
			$r->addWarning('Attempt to use ObjectFilter on the array');

			return 'Array';
		}

		// Check if variable is not an object
		if (!is_object($value)) {
			$r->addWarning('Attempt to use ObjectFilter on non-object');

			return (string) $value;
		}

		// Fetch the modifier to apply on the value
		$nextModifier = array_shift($modifierChain);

		// Check if modifier is valid
		if ($nextModifier == '' && count($modifierChain) == 0) {
			return $value;
		}

		// Parse modifier parameter string
		list($modifierName, $params) = self::parseModifierParameterString($nextModifier);

		// Apply modifier on the value
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
			case "fwdt":
			case "fwdtemplate":
				$moduleName = lavnn('module', $modifierParams, '');
				$templateName = lavnn('name', $modifierParams, '');
				$value = TextProcessor::doTemplate($moduleName, $templateName, $params);
				break;
			case "htmlcomment":
				$value = '<!--'.print_r($value).'-->';
				break;
			case 'dump':
				$value = print_r($value);
				break;
		}

		// Do recursive call if there are more modifiers in the chain
		return (count($modifierChain) > 0) ? Processor::applyModifier($value, $modifierChain, $params) : $value;
	}
}
