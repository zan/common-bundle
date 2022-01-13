<?php


namespace Zan\CommonBundle\Util;


use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Mapping\Annotation;

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
    /**
     * @param Reader $annotationReader
     * @param        $annotationNamespace
     * @param        $annotatedClassNamespace
     * @return null|Annotation
     */
    public static function getClassAnnotation(Reader $annotationReader, $annotationNamespace, $annotatedClassNamespace)
    {
        $refClass = new \ReflectionClass($annotatedClassNamespace);

        $annotation = $annotationReader->getClassAnnotation($refClass, $annotationNamespace);

        return $annotation;
    }

    /**
     * NOTE: This method only works for annotatinos with a getValue() method
     *
     * @param Reader $annotationReader
     * @param        $annotationNamespace
     * @param        $annotatedClassNamespace
     * @return mixed
     */
    public static function getClassAnnotationValue(Reader $annotationReader, $annotationNamespace, $annotatedClassNamespace)
    {
        $annotation = self::getClassAnnotation($annotationReader, $annotationNamespace, $annotatedClassNamespace);

        return ($annotation) ? $annotation->getValue() : null;
    }

    public static function hasClassAnnotation(Reader $annotationReader, $annotationNamespace, $annotatedClassNamespace): bool
    {
        return null !== self::getClassAnnotation($annotationReader, $annotationNamespace, $annotatedClassNamespace);
    }
}