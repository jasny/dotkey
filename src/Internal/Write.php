<?php

declare(strict_types=1);

namespace Jasny\DotKey\Internal;

use Jasny\DotKey\ResolveException;

/**
 * Static class with get and put methods.
 * @internal
 */
final class Write
{
    /**
     * Set a value within the subject by path.
     *
     * @param object|array<string,mixed> $subject
     * @param string                     $path
     * @param mixed                      $value
     * @param string                     $delimiter
     * @throws ResolveException
     */
    public static function set(&$subject, string $path, $value, string $delimiter = '.'): void
    {
        $current =& $subject;

        $index = Helpers::splitPath($path, $delimiter);

        while (count($index) > 1) {
            $key = \array_shift($index);

            if (!\is_array($current) && !\is_object($current)) {
                $msg = "Unable to set '$path': '%s' is of type " . \gettype($current);
                throw ResolveException::create($msg, $path, $delimiter, $index, $key);
            }

            try {
                $current =& Helpers::descend($current, $key, $exists);
            } catch (\Error $error) {
                $msg = "Unable to set '$path': error at '%s'";
                throw ResolveException::create($msg, $path, $delimiter, $index, null, $error);
            }

            if (!$exists) {
                throw ResolveException::create("Unable to set '$path': '%s' doesn't exist", $path, $delimiter, $index);
            }
        }

        if (\is_array($current) || $current instanceof \ArrayAccess) {
            $current[$index[0]] = $value;
        } else {
            try {
                $current->{$index[0]} = $value;
            } catch (\Error $error) {
                throw new ResolveException("Unable to set '$path': error at '$path'", 0, $error);
            }
        }
    }


    /**
     * Set a value, creating a structure if needed.
     *
     * @param object|array<string,mixed> $subject
     * @param string                     $path
     * @param mixed                      $value
     * @param string                     $delimiter
     * @param bool                       $assoc     Create new structure as array. Omit to base upon subject type.
     * @throws ResolveException
     */
    public static function put(&$subject, string $path, $value, string $delimiter = '.', bool $assoc = false): void
    {
        $current =& $subject;

        $index = Helpers::splitPath($path, $delimiter);

        while (count($index) > 1) {
            $key = \array_shift($index);

            try {
                $current =& Helpers::descend($current, $key, $exists);
            } catch (\Error $error) {
                $msg = "Unable to put '$path': error at '%s'";
                throw ResolveException::create($msg, $path, $delimiter, $index, null, $error);
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
            self::setValueCreate($current, $index, $value, $assoc);
        } catch (\Error $error) {
            $msg = "Unable to put '$path': error at '%s'";
            throw ResolveException::create($msg, $path, $delimiter, array_slice($index, 0, -1), null, $error);
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
