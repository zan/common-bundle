<?php

namespace Zan\CommonBundle\Util;

class BundleUtils
{
    /**
     * Given an object or namespace this method returns the name of the bundle by extracting
     * it from the part of the path that includes "Bundle"
     *
     * For example, passing "Corp\SomeProductBundle\Entity\Order" would return "SomeProduct"
     *
     * If no part of the path includes "bundle" this method returns null
     *
     * @param object|class-string $objectOrNamespace
     */
    public static function getBundleName($objectOrNamespace): ?string
    {
        $className = is_string($objectOrNamespace) ? $objectOrNamespace : get_class($objectOrNamespace);

        $parsed = explode("\\", $className);
        foreach ($parsed as $pathPart) {
            if (str_ends_with($pathPart, 'Bundle')) {
                return ZanString::removePostfix($pathPart, 'Bundle');
            }
        }

        return null;
    }
}