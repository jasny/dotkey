<?php

declare(strict_types=1);

namespace Jasny\DotKey\Internal;

use Jasny\DotKey\ResolveException;

/**
 * Static class with get and put methods.
 * @internal
 */
final class Set
{
    /**
     * Set a value within the subject by path.
     *
     * @param object|array<string,mixed> $subject
     * @param string                     $path
     * @param mixed                      $value
     * @param string                     $delimiter
     * @param bool                       $copy
     * @throws ResolveException
     */
    public static function apply(&$subject, string $path, $value, string $delimiter, bool $copy): void
    {
        $current =& $subject;
        $index = Helpers::splitPath($path, $delimiter);

        while (count($index) > 1) {
            if (!\is_array($current) && !\is_object($current)) {
                $msg = "Unable to set '$path': '%s' is of type " . \gettype($current);
                throw ResolveException::create($msg, $path, $delimiter, $index);
            }

            $key = \array_shift($index);

            try {
                $current =& Helpers::descend($current, $key, $exists, false, $copy);
            } catch (\Error $error) {
                $msg = "Unable to set '$path': error at '%s'";
                throw ResolveException::create($msg, $path, $delimiter, $index, $error);
            }

            if (!$exists) {
                throw ResolveException::create("Unable to set '$path': '%s' doesn't exist", $path, $delimiter, $index);
            }
        }

        try {
            if ($copy && is_object($current)) {
                $current = clone $current;
            }

            self::setValue($current, $index[0], $value);
        } catch (\Error $error) {
            throw new ResolveException("Unable to set '$path': error at '$path'", 0, $error);
        }
    }

    /**
     * Set the value of a property or key.
     *
     * @param object|array<string,mixed> $target
     * @param string                     $key
     * @param mixed                      $value
     */
    protected static function setValue(&$target, string $key, $value): void
    {
        if (\is_array($target) || $target instanceof \ArrayAccess) {
            $target[$key] = $value;
        } else {
            $target->{$key} = $value;
        }
    }
}
