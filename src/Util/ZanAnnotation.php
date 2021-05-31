<?php


namespace Zan\CommonBundle\Util;


use Doctrine\Common\Annotations\Reader;

class ZanAnnotation
{
    /**
     * Returns true if the annotation is present
     *
     * @param Reader $annotationReader
     * @param        $annotationNamespace
     * @param        $annotatedClassNamespace
     * @param        $property
     * @return bool
     * @throws \ErrorException
     */
    public static function hasPropertyAnnotation(Reader $annotationReader, $annotationNamespace, $annotatedClassNamespace, $property)
    {
        try {
            $refProperty = ZanObject::getProperty($annotatedClassNamespace, $property);
        } catch (\ErrorException $e) {
            // This most likely means the property doesn't exist, so return false
            return false;
        }

        $annotation = $annotationReader->getPropertyAnnotation($refProperty, $annotationNamespace);

        return ($annotation !== null);
    }
}