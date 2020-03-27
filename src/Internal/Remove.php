<?php

declare(strict_types=1);

namespace Jasny\DotKey\Internal;

use Jasny\DotKey\ResolveException;

/**
 * Static class to remove items from subject with path.
 * @internal
 */
final class Remove
{
    /**
     * Get a particular value back from the config array
     *
     * @param object|array<string,mixed> $subject
     * @param string                     $path
     * @param string                     $delimiter
     * @param bool                       $copy
     */
    public static function apply(&$subject, string $path, string $delimiter, bool $copy): void
    {
        $current =& $subject;
        $index = Helpers::splitPath($path, $delimiter);

        while (\count($index) > 1) {
            $key = \array_shift($index);

            try {
                $current =& Helpers::descend($current, $key, $exists, false, $copy);
            } catch (\Error $error) {
                $msg = "Unable to remove '$path': error at '%s'";
                throw ResolveException::create($msg, $path, $delimiter, $index, $error);
            }

            if (!$exists) {
                return;
            }

            if (!\is_array($current) && !\is_object($current)) {
                $msg = "Unable to remove '$path': '%s' is of type " . \gettype($current);
                throw ResolveException::create($msg, $path, $delimiter, $index);
            }
        }

        try {
            if ($copy && is_object($current)) {
                $current = clone $current;
            }

            self::removeChild($current, $index[0]);
        } catch (\Error $error) {
            $msg = "Unable to remove '$path': error at '%s'";
            throw ResolveException::create($msg, $path, $delimiter, array_slice($index, 0, -1), $error);
        }
    }

    /**
     * Remove item or property from subject.
     *
     * @param object|array<string,mixed> $subject
     * @param string                     $key
     */
    protected static function removeChild(&$subject, string $key): void
    {
        if (\is_array($subject)) {
            if (\array_key_exists($key, $subject)) {
                unset($subject[$key]);
            }
        } elseif ($subject instanceof \ArrayAccess) {
            if ($subject->offsetExists($key)) {
                unset($subject[$key]);
            }
        } elseif (\property_exists($subject, $key)) {
            unset($subject->{$key});
        }
    }
}
