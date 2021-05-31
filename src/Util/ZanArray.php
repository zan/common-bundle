<?php


namespace Zan\CommonBundle\Util;


class ZanArray
{
    /**
     * Attempts to parse a string and split by the best delimiter.
     * If the "=" sign is present, it is used as a key/value delimiter
     * and a hash is returned.  All values are trimmed before they are
     * returned.  Any empty values are not added to the array (unless they have
     * a key)
     *
     * Supported delimiters:
     * \verbatim ; , & \endverbatim
     *
     * This is a port of the legacy ZanUtils::smartSplit method
     *
     * @param mixed $str The string to split
     * @param string $forceDelimiter
     * @param string $forceValueDelimiter
     *
     * @return array An array or hash
     */
    public static function createFromString($str, $forceDelimiter = ";", $forceValueDelimiter = "=")
    {
        // Special case: already an array
        if (is_array($str)) return $str;
        // Special case: empty value
        if (!$str) return array();

        // Search the string for a possible delimiter to use
        $delim = $forceDelimiter;
        if (false === strpos($str, $delim)) $delim = ",";
        if (false === strpos($str, $delim)) $delim = "&";

        // Detect if there's a value delimiter or if it's just a basic array
        $valueDelim = $forceValueDelimiter;
        $hasValueDelim = false;
        if (false !== strpos($str, $valueDelim)) $hasValueDelim = true;

        // If we're on our final delimiter and it's still not in the string, assume that this
        // is a single item string
        if (false === strpos($str, $delim)) {
            if ($hasValueDelim) {
                $parts = explode($valueDelim, $str);
                return array (trim($parts[0]) => trim($parts[1]));
            }
            else {
                return array($str);
            }
        }

        // If our delimiter is a &, we assume it's a url encoded string
        if ($delim == "&") {
            $parsedValues = array();
            parse_str($str, $parsedValues);
            return $parsedValues;
        }
        else {
            // Split by the detected delimiter
            $split = explode($delim, $str);

            $finVals = array();
            foreach ($split as $item) {
                $item = trim($item);
                if ($item == "") continue;

                // If we have equals, create a key in the array
                if ($hasValueDelim) {
                    list($key, $value) = explode($valueDelim, $item);
                    $key = trim($key);
                    $value = trim($value);
                    $finVals[$key] = $value;
                } else {
                    $finVals[] = $item;
                }
            }

            return $finVals;
        }
    }
}