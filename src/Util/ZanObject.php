<?php


namespace Zan\CommonBundle\Util;


class ZanObject
{
    /**
     * Returns all properties on an object including ones inherited from parent
     * classes.
     *
     * @param null|class-string|object $object
     * @return array|\ReflectionMethod[]
     */
    public static function getMethods(null|string|object $object)
    {
        if (null === $object) return [];

        $methods = [];

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
     */
    public static function hasMethod(?object $object, string $methodName): bool
    {
        if (null === $object) return false;

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
     *
     * @param object|class-string $refObject
     */
    public static function getProperty(object|string $refObject, string $property): \ReflectionProperty
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
     * @param null|class-string|\ReflectionClass $object
     * @return array<\ReflectionProperty>
     */
    public static function getProperties(null|string|\ReflectionClass $object)
    {
        if (null === $object) return [];

        $properties = [];

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

        // Property is nested, get top-level property and then recurse below
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
                $getterFn = [$object, $getterMethod];
                if (!is_callable($getterFn)) throw new \LogicException('Method is not callable');
                $value = call_user_func($getterFn);
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
            if (!is_object($value)) throw new \LogicException('Attempted to recurse into a non-object');
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
     * @param class-string|object $object
     */
    public static function setProperty(string|object $object, string $propertyName, mixed $value): bool
    {
        // Determine the setter to use
        $setterName = sprintf("set%s", ucfirst($propertyName));

        // Make sure the setter is valid
        if (!self::hasPublicMethod($object, $setterName)) {
            return false;
        }

        $setterFn = [$object, $setterName];
        if (!is_callable($setterFn)) throw new \LogicException('Setter was not callable');

        call_user_func($setterFn, $value);

        return true;
    }

    /**
     * Returns true if $object has $methodName on itself or any parent classes
     * and the method is public.
     *
     * @param class-string|object $object
     */
    public static function hasPublicMethod(string|object $object, string $methodName): bool
    {
        $methods = self::getMethods($object);

        foreach ($methods as $foundMethod) {
            if ($methodName == $foundMethod->getName() && $foundMethod->isPublic()){
                return true;
            }
        }

        return false;
    }

    /**
     * @param class-string|object $object
     * @return \ReflectionParameter[]
     */
    public static function getRequiredConstructorArguments(string|object $object): array
    {
        $class = new \ReflectionClass($object);
        $constructor = $class->getConstructor();

        // Early exit if there isn't a constructor
        if (!$constructor) return [];

        $required = [];
        foreach ($constructor->getParameters() as $parameter) {
            // Only interested in required ones
            if ($parameter->isOptional()) continue;

            $required[] = $parameter;
        }

        return $required;
    }
}