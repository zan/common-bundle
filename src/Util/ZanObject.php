<?php


namespace Zan\CommonBundle\Util;


class ZanObject
{
    /**
     * Returns all properties on an object including ones inherited from parent
     * classes.
     *
     * @param $object
     * @return array|\ReflectionMethod[]
     */
    public static function getMethods($object)
    {
        $methods = array();

        if ($object instanceof \ReflectionClass) {
            $refClass = $object;
        }
        else {
            $refClass = new \ReflectionClass($object);
        }

        do {
            $objMethods = $refClass->getMethods();
            $methods = array_merge($methods, $objMethods);

            // private methods will not show up on $object so we must
            // iterate through parent classes
            $refClass = $refClass->getParentClass();
        } while ($refClass);

        return $methods;
    }

    /**
     * Returns true if $object has $methodName on itself or any parent classes
     *
     * @param $object
     * @param $methodName
     * @return bool
     */
    public static function hasMethod($object, $methodName)
    {
        $methods = self::getMethods($object);

        foreach ($methods as $foundMethod) {
            if ($methodName == $foundMethod->getName()){
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the specified property of an object
     *
     * This method will traverse parent classes and return inherited properties
     */
    public static function getProperty(object|string $refObject, string $property)
    {
        if (!$refObject instanceof \ReflectionClass) {
            $refObject = new \ReflectionClass($refObject);
        }

        $refObjectClassName = $refObject->getName();

        // getProperty() will only return properties on the object and NOT any
        // defined on parent classes. We must traverse the object hierarchy until
        // we find the specified property
        while (!$refObject->hasProperty($property)) {
            $refObject = $refObject->getParentClass();

            if (!$refObject) {
                throw new \ErrorException(sprintf("Could not find property %s in %s or any of its parent classes", $property, $refObjectClassName));
            }
        }

        return $refObject->getProperty($property);
    }

    /**
     * @return array<\ReflectionProperty>
     */
    public static function getProperties(string|\ReflectionClass $object)
    {
        $properties = array();

        if ($object instanceof \ReflectionClass) {
            $refClass = $object;
        }
        else {
            $refClass = new \ReflectionClass($object);
        }

        do {
            $objProperties = $refClass->getProperties();
            $properties = array_merge($properties, $objProperties);

            // private properties will not show up on $object so we must
            // iterate through parent classes
            $refClass = $refClass->getParentClass();
        } while ($refClass);

        return $properties;
    }

    /**
     * Returns the value of the specified property for the given object. Note that
     * this will work for any property including protected and private ones.
     *
     * You can find the value of a nested property by using dot notation.
     * Example:
     *      ZanObject::getPropertyValue($user, 'department.name', true);
     *
     * This would call $user->getDepartment()->getName();
     *
     * @param object $object
     * @param string|\ReflectionProperty $propertyName
     * @param boolean $useGetter If available, use the getter method
     * @return mixed
     */
    public static function getPropertyValue($object, $propertyName, $useGetter = false)
    {
        // Support nested properties like object.user.name
        $isNested = false;
        $remainingProperties = null;
        $value = null;
        if ($propertyName instanceof \ReflectionProperty) {
            $propertyName = $propertyName->getName();
        }

        // Property is nested, get top-leve property and then recurse below
        if (false !== stristr($propertyName, '.')) {
            $isNested = true;
            $parts = explode('.', $propertyName);
            $propertyName = array_shift($parts);
            $remainingProperties = join('.', $parts);
        }

        // If we're using a getter, check to see if one is available
        if ($useGetter) {
            $getterMethod = sprintf("get" . ucfirst($propertyName));
            if (self::hasMethod($object, $getterMethod)) {
                $value = call_user_func([$object, $getterMethod]);
            }
            else {
                // set $useGetter to false so we fall back to accessing property
                // directly
                $useGetter = false;
            }
        }

        // This is !$useGetter instead of an else so that we can fall back to this
        // method if there's no getter implemented (see above)
        if (!$useGetter) {
            $property = self::getProperty($object, $propertyName);

            $propertyOrigAccessible = $property->isPublic();
            $property->setAccessible(true);

            $value = $property->getValue($object);

            $property->setAccessible($propertyOrigAccessible);
        }

        // If it's nested recursively call with one level down
        if ($isNested) {
            return self::getPropertyValue($value, $remainingProperties, $useGetter);
        }
        // No remaining properties, return the property on the current object
        else {
            return $value;
        }
    }

    /**
     * Sets $propertyName on $object to $value if a public setter can be found.
     *
     * Setters are searched for by uppercasing the first letter of $propertyName
     * and prepending 'set'
     *
     * This method returns true if the setter was found or false otherwise
     *
     * @param $object
     * @param $propertyName
     * @param $value
     * @return bool
     */
    public static function setProperty($object, $propertyName, $value)
    {
        // Determine the setter to use
        $setterFn = sprintf("set%s", ucfirst($propertyName));

        // Make sure the setter is valid
        if (!self::hasPublicMethod($object, $setterFn)) {
            return false;
        }

        call_user_func(array($object, $setterFn), $value);

        return true;
    }

    /**
     * Returns true if $object has $methodName on itself or any parent classes
     * and the method is public.
     *
     * @param $object
     * @param $methodName
     * @return bool
     */
    public static function hasPublicMethod($object, $methodName)
    {
        $methods = self::getMethods($object);

        foreach ($methods as $foundMethod) {
            if ($methodName == $foundMethod->getName() && $foundMethod->isPublic()){
                return true;
            }
        }

        return false;
    }
}