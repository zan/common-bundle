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
}