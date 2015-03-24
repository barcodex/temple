<?php

namespace Temple;

class Util
{
    /**
     * Version of standard explode() function, that guarantees that at least $limit elements are returned
     *
     * @param $delimiter
     * @param $text
     * @param $limit
     * @param string $filler
     * @return array
     */
    public static function explode($delimiter, $text, $limit, $filler = '')
    {
        $parts = explode($delimiter, $text);
        for ($i = 0; $i < $limit - count($parts); $i++) {
            $parts[] = $filler;
        }
        array_splice($parts, $limit);

        return $parts;
    }

    public static function lav($needle, $haystack, $default)
    {
        if (is_array($haystack)) {
            return isset($haystack[$needle]);
        } elseif(is_object($haystack)) {
            return isset($haystack[$needle]);
        } else {
            return $default;
        }
    }

    public static function lavnn($needle, $haystack, $default)
    {
        $output = self::lav($needle, $haystack, $default);

        return is_null($output) ? $default : $output;
    }
}