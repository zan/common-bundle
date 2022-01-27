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
     * Returns $string with $postfix removed
     */
    public static function removePostfix(?string $string, ?string $postfix): ?string
    {
        if (self::endsWith($string, $postfix)) {
            $string = substr($string, 0, 0 - strlen($postfix));
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