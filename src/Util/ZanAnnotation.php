<?php


namespace Zan\CommonBundle\Util;


use Doctrine\Common\Annotations\Reader;

class ZanAnnotation
{
    /**
     * Returns true if the annotation is present
     *
     * @param class-string $annotationNamespace
     * @param class-string $annotatedClassNamespace
     */
    public static function hasPropertyAnnotation(Reader $annotationReader, string $annotationNamespace, string $annotatedClassNamespace, string $property): bool
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
     * @param class-string $annotationNamespace
     * @param class-string $annotatedClassNamespace
     *
     * @return object|string|null
     */
    public static function getClassAnnotation(Reader $annotationReader, string $annotationNamespace, string $annotatedClassNamespace)
    {
        $refClass = new \ReflectionClass($annotatedClassNamespace);

        $annotation = $annotationReader->getClassAnnotation($refClass, $annotationNamespace);

        return $annotation;
    }

    /**
     * NOTE: This method only works for annotations with a getValue() method
     *
     * @param class-string $annotationNamespace
     * @param class-string $annotatedClassNamespace
     *
     * @return mixed
     */
    public static function getClassAnnotationValue(Reader $annotationReader, string $annotationNamespace, string $annotatedClassNamespace)
    {
        $annotation = self::getClassAnnotation($annotationReader, $annotationNamespace, $annotatedClassNamespace);

        if (!$annotation) return null;

        if (!method_exists($annotation, 'getValue')) throw new \InvalidArgumentException('Annotation must implement getValue()');

        return $annotation->getValue(); // @phpstan-ignore-line
    }

    /**
     * @param class-string $annotationNamespace
     * @param class-string $annotatedClassNamespace
     */
    public static function hasClassAnnotation(Reader $annotationReader, string $annotationNamespace, string $annotatedClassNamespace): bool
    {
        return null !== self::getClassAnnotation($annotationReader, $annotationNamespace, $annotatedClassNamespace);
    }
}