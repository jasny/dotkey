<?php

declare(strict_types=1);

namespace Jasny\DotKey;

/**
 * Access objects and arrays through dot notation.
 */
class DotKey
{
    public const COPY = 0b1;

    /**
     * @var object|array<string,mixed>
     */
    protected object|array $subject;

    protected bool $copy;


    /**
     * Class constructor.
     *
     * @param object|array<string,mixed> $subject
     * @param int                        $opts     Binary set of options
     */
    public function __construct(array|object &$subject, int $opts = 0)
    {
        $this->subject =& $subject;
        $this->copy = (bool)($opts & self::COPY);
    }


    /**
     * Check if path exists in subject.
     */
    public function exists(string $path, string $delimiter = '.'): bool
    {
        return Internal\Read::exists($this->subject, $path, $delimiter);
    }

    /**
     * Get a value from subject by path.
     *
     * @throws ResolveException
     */
    public function get(string $path, string $delimiter = '.'): mixed
    {
        return Internal\Read::get($this->subject, $path, $delimiter);
    }


    /**
     * Set a value within the subject by path.
     *
     * @throws ResolveException
     */
    public function set(string $path, mixed $value, string $delimiter = '.'): void
    {
        if ($this->copy && Internal\Read::same($this->subject, $path, $delimiter, $value)) {
            return;
        }

        Internal\Set::apply($this->subject, $path, $value, $delimiter, $this->copy);
    }

    /**
     * Set a value, creating a structure if needed.
     *
     * @throws ResolveException
     */
    public function put(string $path, mixed $value, string $delimiter = '.', ?bool $assoc = null): void
    {
        if ($this->copy && Internal\Read::same($this->subject, $path, $delimiter, $value)) {
            return;
        }

        $assoc ??= is_array($this->subject) || $this->subject instanceof \ArrayAccess;

        Internal\Put::apply($this->subject, $path, $value, $delimiter, $assoc, $this->copy);
    }

    /**
     * Get a particular value back from the config array
     */
    public function remove(string $path, string $delimiter = '.'): void
    {
        if ($this->copy && !Internal\Read::exists($this->subject, $path, $delimiter)) {
            return;
        }

        Internal\Remove::apply($this->subject, $path, $delimiter, $this->copy);
    }

    /**
     * Update a value within the subject by path.
     *
     * @throws ResolveException
     */
    public function update(string $path, callable $callback, string $delimiter = '.'): void
    {
        $value = $this->get($path, $delimiter);
        $this->set($path, $callback($value), $delimiter);
    }


    /**
     * Factory method.
     *
     * @param object|array<string,mixed> $subject
     * @return static
     */
    public static function on(array|object &$subject): self
    {
        return new static($subject);
    }

    /**
     * Factory method.
     *
     * @param object|array<string,mixed> $source
     * @param mixed                      $copy
     * @return static
     */
    public static function onCopy(array|object $source, mixed &$copy): self
    {
        $copy = $source;

        return new static($copy, self::COPY);
    }
}
