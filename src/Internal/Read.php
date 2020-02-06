<?php

declare(strict_types=1);

namespace Jasny\DotKey\Internal;

use Jasny\DotKey\ResolveException;

/**
 * Static methods to read subject using path.
 * @internal
 */
final class Read
{
    /**
     * Check if path exists in subject.
     *
     * @param object|array<string,mixed> $subject
     * @param string                     $path
     * @param string                     $delimiter
     * @return bool
     */
    public static function exists($subject, string $path, string $delimiter = '.'): bool
    {
        $index = Helpers::splitPath($path, $delimiter);

        foreach ($index as $key) {
            if (!\is_array($subject) && !\is_object($subject)) {
                return false;
            }

            $subject = Helpers::descend($subject, $key, $exists, true);

            if (!$exists) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get a value from subject by path.
     *
     * @param object|array<string,mixed> $subject
     * @param string                     $path
     * @param string                     $delimiter
     * @return mixed
     * @throws ResolveException
     */
    public static function get($subject, string $path, string $delimiter = '.')
    {
        $index = Helpers::splitPath($path, $delimiter);

        while ($index !== []) {
            $key = \array_shift($index);

            if (!\is_array($subject) && !\is_object($subject)) {
                $msg = "Unable to get '$path': '%s' is of type " . \gettype($subject);
                throw ResolveException::create($msg, $path, $delimiter, $index, $key);
            }

            try {
                $subject = Helpers::descend($subject, $key, $exists);
            } catch (\Error $error) {
                $msg = "Unable to get '$path': error at '%s'";
                throw ResolveException::create($msg, $path, $delimiter, $index, null, $error);
            }

            if (!$exists) {
                return null;
            }
        }

        return $subject;
    }
}
