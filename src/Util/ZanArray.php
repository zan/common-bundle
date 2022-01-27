<?php


namespace Zan\CommonBundle\Util;


class ZanArray
{
    /**
     * Similar to explode with support for handling things that may already be arrays or may be empty
     *
     * @param null|string|array<mixed> $str
     * @param non-empty-string $separator
     * @return array<string>
     */
    public static function createFromString(null|string|array $str, string $separator = ","): array
    {
        // Special case: already an array
        // @phpstan-ignore-next-line
        if (is_array($str)) return $str;

        // Special case: empty value
        if (!$str) return [];

        return explode($separator, $str);
    }
}