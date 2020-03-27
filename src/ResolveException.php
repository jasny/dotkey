<?php

declare(strict_types=1);

namespace Jasny\DotKey;

/**
 * Exception throw if path can't be resolved.
 */
class ResolveException extends \RuntimeException
{
    /**
     * Create a ResolveException, filling out %s with invalid path.
     *
     * @param string   $msg
     * @param string   $path
     * @param string   $delimiter
     * @param string[] $index
     * @throws ResolveException
     */
    public static function create(
        string $msg,
        string $path,
        string $delimiter,
        array $index,
        ?\Throwable $previous = null
    ): ResolveException {
        $len = ($index !== [] ? \strlen($delimiter) + \strlen(\join($delimiter, $index)) : 0);
        $invalidPath = $len > 0 ? \substr(\rtrim($path, $delimiter), 0, -1 * $len) : $path;

        return new ResolveException(sprintf($msg, $invalidPath), 0, $previous);
    }
}
