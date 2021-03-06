<?php

declare(strict_types=1);

namespace Jasny\DotKey\Internal;

/**
 * Static class with helper functions.
 * @internal
 */
final class Helpers
{
    /**
     * Make subject a child of the subject.
     * If `$exists` is false, it wasn't possible to decent and subject is returned.
     *
     * @param object|array<string,mixed> $subject
     * @param string                     $key
     * @param mixed                      $exists      output as bool
     * @param bool                       $accessible  Check not only if property exists, but also is accessible.
     * @param bool                       $copy        Copy objects
     * @return mixed
     */
    public static function &descend(&$subject, string $key, &$exists, bool $accessible = false, bool $copy = false)
    {
        if ($copy && is_object($subject)) {
            $subject = clone $subject;
        }

        if (\is_array($subject) || $subject instanceof \ArrayAccess) {
            return self::descendArray($subject, $key, $exists);
        } else {
            return self::descendObject($subject, $key, $exists, $accessible);
        }
    }

    /**
     * @param array<string,mixed>|\ArrayAccess<string,mixed> $subject
     * @param string                                         $key
     * @param mixed                                          $exists
     * @return mixed
     */
    private static function &descendArray(&$subject, string $key, &$exists)
    {
        $exists = \is_array($subject) ? \array_key_exists($key, $subject) : $subject->offsetExists($key);

        if ($exists) {
            $subject =& $subject[$key];
        }

        return $subject;
    }

    /**
     * @param object $subject
     * @param string $key
     * @param mixed  $exists
     * @param bool   $accessible
     * @return mixed
     */
    private static function &descendObject(object &$subject, string $key, &$exists, bool $accessible)
    {
        $exists = $accessible
            ? self::propertyIsAccessible($subject, $key)
            : \property_exists($subject, $key);

        if ($exists) {
            $subject =& $subject->{$key};
        }

        return $subject;
    }

    /**
     * Check if property exists and is accessible.
     *
     * @param object $object
     * @param string $property
     * @return bool
     */
    public static function propertyIsAccessible(object $object, string $property): bool
    {
        $exists = \property_exists($object, $property);

        if (!$exists || isset($object->{$property})) {
            return $exists;
        }

        try {
            $reflection = new \ReflectionProperty($object, $property);
        } catch (\ReflectionException $exception) { // @codeCoverageIgnore
            return false;                           // @codeCoverageIgnore
        }

        return $reflection->isPublic() && !$reflection->isStatic();
    }

    /**
     * Explode with trimming and check.
     * @see explode()
     *
     * @param string $path
     * @param string $delimiter
     * @return string[]
     */
    public static function splitPath(string $path, string $delimiter): array
    {
        if ($delimiter === '') {
            throw new \InvalidArgumentException("Delimiter can't be an empty string");
        }

        /** @var array<int,string> $parts */
        $parts = \explode($delimiter, trim($path, $delimiter));

        return $parts;
    }
}
