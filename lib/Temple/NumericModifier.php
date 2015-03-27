<?php

namespace Temple;

class NumericModifier
	extends Modifier
{
	/** @inheritdoc */
	public static function apply(
        $name,
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
        $value = self::calculateValue($modifierName, $modifierParams, $value, $params);

		// Do recursive call if there are more modifiers in the chain
		return (count($modifierChain) > 0) ? Processor::applyModifier($value, $modifierChain, $params) : $value;
	}

    /** @inheritdoc */
    public static function calculateValue($modifierName, $modifierParams, $value, $params = array())
    {
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
            case 'stopifnotnull':
                if (!is_null($value)) {
                    return '';
                }
                break;
            case 'ifnotnull':
            case 'stopifnull':
                if (is_null($value)) {
                    return '';
                }
                break;
            case "htmlcomment":
                $value = "<!--$value-->";
                break;
            case "dump":
                $value = print_r($value, 1);
                break;
            case 'replace':
                $default = Util::lavnn('default', $modifierParams, '');
                $fallback = Util::lavnn('fallback', $modifierParams, '');
                $value = Util::lavnn($fallback, $params, $default);
                break;
            case "checked":
                $value = ($value == '1' ? 'checked' : '');
                break;
            case "fixbool":
                $value = (bool) ($value);
                break;
            case 'round':
                $value = round($value, Util::lavnn('digits', $modifierParams, 0));
                break;
            case 'money':
                $value = round($value, 2);
                break;
            case 'date':
                $value = date('d.m.Y H:i:s', $value);
                break;
            case 'thousands':
                // @TODO make it configurable from locale. so far usable only for admin purposes
                $value = number_format($value, 0, '', '.');
                break;
            case 'checkboxvalue':
                $avalue = $value ? 'checked="checked"' : '';
                break;
        }

        return $value;
    }
}
