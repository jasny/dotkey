<?php

declare(strict_types=1);

namespace Jasny\DotKey\Internal;

use Jasny\DotKey\ResolveException;

/**
 * Static class with get and put methods.
 * @internal
 */
final class Put
{
    /**
     * Set a value, creating a structure if needed.
     *
     * @param object|array<string,mixed> $subject
     * @param string                     $path
     * @param mixed                      $value
     * @param string                     $delimiter
     * @param bool                       $assoc     Create new structure as array. Omit to base upon subject type.
     * @param bool                       $copy
     * @throws ResolveException
     */
    public static function apply(&$subject, string $path, $value, string $delimiter, bool $assoc, bool $copy): void
    {
        $current =& $subject;
        $index = Helpers::splitPath($path, $delimiter);

        while (count($index) > 1) {
            $key = \array_shift($index);

            try {
                $current =& Helpers::descend($current, $key, $exists, false, $copy);
            } catch (\Error $error) {
                $msg = "Unable to put '$path': error at '%s'";
                throw ResolveException::create($msg, $path, $delimiter, $index, $error);
            }

            if (!$exists) {
                array_unshift($index, $key);
                break;
            }

            if (!\is_array($current) && !\is_object($current)) {
                break;
            }
        }

        try {
            if ($copy && is_object($current)) {
                $current = clone $current;
            }

            self::setValueCreate($current, $index, $value, $assoc);
        } catch (\Error $error) {
            $msg = "Unable to put '$path': error at '%s'";
            throw ResolveException::create($msg, $path, $delimiter, array_slice($index, 0, -1), $error);
        }
    }

    /**
     * Create property and set the value.
     *
     * @param mixed     $subject
     * @param string[]  $index    Part or the path that doesn't exist
     * @param mixed     $value
     * @param bool      $assoc
     */
    protected static function setValueCreate(&$subject, array $index, $value, bool $assoc): void
    {
        if (is_array($subject) || $subject instanceof \ArrayAccess) {
            $key = \array_shift($index);
            $subject[$key] = null;
            $subject =& $subject[$key];
        } elseif (is_object($subject)) {
            $key = \array_shift($index);
            $subject->{$key} = null;
            $subject =& $subject->{$key};
        }

        while ($index !== []) {
            $key = \array_pop($index);
            $value = $assoc ? [$key => $value] : (object)[$key => $value];
        }

        $subject = $value;
    }
}
