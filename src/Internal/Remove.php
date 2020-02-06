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
     * @return object|array<string,mixed>
     *
     * @template TSubject
     * @phpstan-param TSubject&(object|array<string,mixed>) $subject
     * @phpstan-param string                                $path
     * @phpstan-param string                                $delimiter
     * @phpstan-return TSubject&(object|array<string,mixed>)
     */
    public static function remove($subject, string $path, string $delimiter = '.')
    {
        $result =& $subject;

        $index = Helpers::splitPath($path, $delimiter);

        while (\count($index) > 1) {
            $key = \array_shift($index);

            try {
                $subject =& Helpers::descend($subject, $key, $exists);
            } catch (\Error $error) {
                $msg = "Unable to remove '$path': error at '%s'";
                throw ResolveException::create($msg, $path, $delimiter, $index, null, $error);
            }

            if (!$exists) {
                return $result;
            }

            if (!\is_array($subject) && !\is_object($subject)) {
                $msg = "Unable to remove '$path': '%s' is of type " . \gettype($subject);
                throw ResolveException::create($msg, $path, $delimiter, $index);
            }
        }

        try {
            self::removeChild($subject, $index[0]);
        } catch (\Error $error) {
            $msg = "Unable to remove '$path': error at '%s'";
            throw ResolveException::create($msg, $path, $delimiter, array_slice($index, 0, -1), null, $error);
        }

        return $result;
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