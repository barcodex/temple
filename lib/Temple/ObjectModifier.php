<?php

namespace Temple;

class ObjectModifier
	extends Modifier
{
    /** @inheritdoc */
    public static function apply(
        $name,
        $value,
        $modifierChain,
        $params = array()
    )
    {
        // Check if variable is of array type
        if (is_array($value)) {
            return 'Array';
        }

        // Check if variable is not an object
        if (!is_object($value)) {
            return (string)$value;
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
                $value = '<!--' . print_r($value) . '-->';
                break;
            case 'dump':
                $value = print_r($value);
                break;
        }

        return $value;
    }
}