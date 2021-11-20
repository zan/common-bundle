<?php


namespace Zan\CommonBundle\Util;


class ZanString
{
    /**
     * Returns $string with $prefix removed
     */
    public static function removePrefix(string $string, string $prefix): string
    {
        if (str_starts_with($string, $prefix)) {
            $string = substr($string, strlen($prefix));
        }

        return $string;
    }

    /**
     * Returns true if $haystack ends with $needle
     */
    public static function endsWith(?string $haystack, string $needle): bool
    {
        if (!$haystack) return false;

        $strpos = strrpos($haystack, $needle);
        if ($strpos === false) return false;

        return (strrpos($haystack, $needle) === strlen($haystack) - strlen($needle));
    }
}