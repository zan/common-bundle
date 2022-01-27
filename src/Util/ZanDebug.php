<?php

namespace Zan\CommonBundle\Util;

class ZanDebug
{
    public static function dump($args)
    {
        // Silently exit if dump() is not a function
        if (!function_exists('dump')) return;

        call_user_func_array('dump', func_get_args());
    }
}