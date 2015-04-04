<?php

namespace Temple;

/**
 * Processor
 *
 * Our templates are pretty dumb, flat and simple, they do not have any control flow.
 * All the work to build output from templates is given to controllers.
 * Templates are just fragments of texts with placeholders, and some of the placeholders can have modifiers.
 * There's no pre-compilation of templates - we believe that trivial parsing of text strings should be quick enough.
 * Processor class actually works with texts, and its consumer is responsible for reading/caching templates.
 *
 * Processor class is extendable:
 *  - if tag syntax gets more complicated, overwrite doTag() and/or doTagContext() functions.
 *  - if specific modifiers are to be introduced, overwrite applyModifier() to use the right derivative of Modifier
 *
 */
class Processor
{
	/**
	 * Generic function to process text, using provided tags markup.
	 * Processing text leaves intact all text between the tags, which are recognized by $startMark and $endMark.
	 * The tags themselves are processed by doTag function, which substitutes each of them with a string.
	 *
	 * @param $text
	 * @param $startMark
	 * @param $endMark
	 * @param array $params
	 *
	 * @return string
	 */
	public static function doTextVariation(
		$text,
		$startMark,
		$endMark,
		$params = array()
	) {
		$parts = explode($startMark, $text); // beginnings of all placeholders
		$output = array_shift($parts); // text before first placeholder
		foreach ($parts as $part) {
			list($tag, $staticText) = Util::explode($endMark, $part, 2); // placeholder and text before the next one
			//list($tag, $staticText) = mb_split($endMark, $part, 2); // placeholder and text before the next one
			list($tagContext, $tagName) = Util::explode(':', $tag, 2); // tag name and tag value to process
            if ($tagName == '') {
                // this looks like tag context was omitted, so we just suppose it was "v"
                $tagName = $tagContext;
                $tagContext = 'v';
            }
            $output .= (self::doTag($tagContext, $tagName, $params) . $staticText);
		}

		return $output;
	}

	/**
	 * Processes a text using default tag variation ( {{tag}} )
	 *
	 * @param $text
	 * @param array $params
	 *
	 * @return string
	 */
	public static function doText(
		$text,
		$params = array()
	) {
		return self::doTextVariation($text, '{{', '}}', $params);
	}

	/**
	 * Processes the same text with each of the $rows, joining the result
	 *
	 * @param $text
	 * @param $rows
	 *
	 * @return string
	 */
	public static function loopText(
		$text,
		$rows
	) {
		$output = array();
		foreach ($rows as $row) {
			$output[] = self::doText($text, $row);
		}

		return join('', $output);
	}

	/**
	 * Processes a single tag, parsed by doText and returns back its substituted value
	 *
	 * @param $context
	 * @param $pathWithModifiers
	 * @param array $params
	 *
	 * @return string
	 */
	public static function doTag(
		$context,
		$pathWithModifiers,
		$params = array()
	) {
		$output = '';

		// Extract filters from placeholder definition, if any
		$modifiers = explode("|", $pathWithModifiers); // modifiers can be piped
		$elementPath = array_shift($modifiers); // text before the first pipe sign is the the name of the element to search for in $params
		// After getting first element out of $modifiers array, we really have only filters in it; applyModifier() will take care of it

		// Process tags according to their name
		switch ($context) {
			case 'pass': // preserve the original {{tag}} - useful for multiple-pass parsing
				$output = '{{' . $pathWithModifiers . '}}';
				break;
			case '_': // based on no value - simple way to include child templates
				$value = '';
				$output = array(count($modifiers) > 0) ? self::applyModifier($elementPath, $value, $modifiers, $params) : $value;
				break;
			case 'v': // named element from associative array $params
				$value = self::getTagValue($elementPath, $params);
				$output = count($modifiers) > 0 ? self::applyModifier($elementPath, $value, $modifiers, $params) : $value;
				break;
			case 'srv':
			case 'server': // named element from $_SERVER array
				$value = self::getTagValue($elementPath, $_SERVER);
				$output = count($modifiers) > 0 ? self::applyModifier($elementPath, $value, $modifiers, $params) : $value;
				break;
			case 'req':
			case 'request': // named element from $_REQUEST array
				$value = self::getTagValue($elementPath, $_REQUEST);
				$output = count($modifiers) > 0 ? self::applyModifier($elementPath, $value, $modifiers, $params) : $value;
				break;
			case 'sess':
			case 'session': // named element from $_SESSION array
				$value = self::getTagValue($elementPath, $_SESSION);
				$output = count($modifiers) > 0 ? self::applyModifier($elementPath, $value, $modifiers, $params) : $value;
				break;
			case 'cookie': // named cookie
				$value = self::getTagValue($elementPath, $_COOKIE);
				$output = count($modifiers) > 0 ? self::applyModifier($elementPath, $value, $modifiers, $params) : $value;
				break;
			default:
                $output = self::doTagContext($context, $elementPath, $params);
				break;
		}

		// Make sure that final output is string
		if (is_array($output)) {
			$output = 'Array';
		} elseif (is_object($output)) {
			$output = 'Object';
		} else {
			$output = (string) $output;
		}

		return $output;
	}

    /**
     * Overwrite this method in Processor's derivative class to allow more contexts in addition to the ones in doTag()
     *
     * @param $context
     * @param $elementPath
     * @param $params
     * @return string
     */
    public static function doTagContext($context, $elementPath, $params)
    {
        return '';
    }

	/**
	 * Calculates the tag value. Also handles values specified as array elements, so the following $needle are possible:
	 *  'first_name'
	 *  'customer.first_name'
	 *  'customer.billing_address.zip'
	 *  'customer.billing_address.country.name'
	 *
	 * @param string $needle
	 * @param array $haystack
	 *
	 * @return mixed
	 */
	public static function getTagValue(
		$needle,
		$haystack
	) {
		// check arguments and return an empty string if something is wrong
		if ($needle == '' || !is_array($haystack)) {
			return '';
		}

		// search $haystack for any
		$pathElements = explode('.', $needle);
		do {
            // take the next element. this ens
			$pathElement = array_shift($pathElements);
			if (array_key_exists($pathElement, $haystack)) {
				$haystack = $haystack[$pathElement];
			} else {
                // @TODO provide some reporting or throw an exception
				return '';
			}
		} while (count($pathElements) > 0);

		return $haystack; // what remains after traversing the path is what we wanted to get
	}

	/**
	 * Applies filter chain to the $value.
	 * Depending on the type of the $value, different filter handler classes are used.
	 * Their apply() methods will return here to decide the handler class for the next filter in the chain
	 *
	 * @param $value
	 * @param $filters
	 * @param array $params
	 *
	 * @return string
	 */
	public static function applyModifier(
		$value,
		$filters,
		$params = array()
	) {
		if (is_object($value)) {
			return ObjectModifier::apply($value, $filters, $params);
		} else if (is_array($value)) {
			return ArrayModifier::apply($value, $filters, $params);
		} else if (is_numeric($value)) {
			return NumericModifier::apply($value, $filters, $params);
		} else {
			return ScalarModifier::apply($value, $filters, $params);
		}
	}

	/**
	 * Searches first function in call stack that matches $functionNames and return its arguments
	 * @TODO move to Runtime
	 *
	 * @param $functionNames
	 *
	 * @return array
	 */
	public static function getCallerArgs($functionNames) {
		$output = array();

		foreach (debug_backtrace() as $caller) {
			if (in_array($caller['function'], $functionNames)) {
				$output = $caller['args'];
				break;
			}
		}

		return $output;
	}

	public static function glueDecoder($glueCode)
	{
		$mapping = array(
			'none' => '',
			'space' => ' ',
			'comma' => ',',
			'quotecomma' => "','",
			'colon' => ':',
			'semicolon' => ';',
			'newline' =>  PHP_EOL
		);

		return Util::lavnn($glueCode, $mapping, '');
	}

}
