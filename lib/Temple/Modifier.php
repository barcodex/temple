<?php

namespace Temple;

abstract class Modifier
{
	/**
     * Applies first modifier from $modifierChain on the given $value.
     *  The caller code is responsible for providing the parameters.
     *  Since value can change its type after applying the modifier,
     *  control is returned to Processor to define the next Modifier
     *  class for the next modifier in the pipeline.
     *
	 * @param string $name
	 * @param $value
	 * @param $modifierChain
	 * @param array $params
	 *
	 * @return mixed
	 */
	protected static function apply(
		$name,
		$value,
		$modifierChain,
		$params = array()
	)
	{
		return $value;
	}

	/**
	 * Parses filter definition. Returns array( string $name, array $params )
	 *
	 * @param $parameterString
	 *
	 * @return array
	 */
	protected static function parseModifierParameterString($parameterString)
	{
		$parts = explode("?", $parameterString, 2);
		$name = $parts[0];
		$params = array();
		if (count($parts) > 1) {
			parse_str($parts[1], $params);
		}

		return array($name, $params);
	}

    /**
     * This is the method that does the job of modifying the value, so extend this if you want to introduce new modifiers.
     *
     * @param $modifierName
     * @param array $modifierParams
     * @param $value
     * @param array $params
     * @return mixed
     */
    protected static function calculateValue($modifierName, $modifierParams, $value, $params = array())
    {
        switch ($modifierName) {
            case 'iftrue':
            case 'stopiffalse':
                $value = (bool) $value;
                if (!$value || empty($value)) {
                    return '';
                }
                break;
            case 'iffalse':
            case 'stopiftrue':
                $value = (bool) $value;
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
                $value = '<!--' . print_r($value, 1) . '-->';
                break;
            case 'dump':
                $value = print_r($value, 1);
                if (isset($modifierParams['pre'])) {
                    $value = "<pre>$value</pre>";
                }
                break;
        }

        return $value;
    }
}
