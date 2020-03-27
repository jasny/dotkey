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
    protected $subject;

    protected bool $copy;


    /**
     * Class constructor.
     *
     * @param object|array<string,mixed> $subject
     * @param int                        $opts     Binary set of options
     */
    public function __construct(&$subject, int $opts = 0)
    {
        if (!\is_object($subject) && !\is_array($subject)) {
            $type = \gettype($subject);
            throw new \InvalidArgumentException("Subject should be an array or object; $type given");
        }

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
     * @param string $path
     * @param string $delimiter
     * @return mixed
     * @throws ResolveException
     */
    public function get(string $path, string $delimiter = '.')
    {
        return Internal\Read::get($this->subject, $path, $delimiter);
    }


    /**
     * Set a value within the subject by path.
     *
     * @param string $path
     * @param mixed  $value
     * @param string $delimiter
     * @throws ResolveException
     */
    public function set(string $path, $value, string $delimiter = '.'): void
    {
        if ($this->copy && Internal\Read::same($this->subject, $path, $delimiter, $value)) {
            return;
        }

        Internal\Set::apply($this->subject, $path, $value, $delimiter, $this->copy);
    }

    /**
     * Set a value, creating a structure if needed.
     *
     * @param string    $path
     * @param mixed     $value
     * @param string    $delimiter
     * @param bool|null $assoc     Create new structure as array. Omit to base upon subject type.
     * @throws ResolveException
     */
    public function put(string $path, $value, string $delimiter = '.', ?bool $assoc = null): void
    {
        if ($this->copy && Internal\Read::same($this->subject, $path, $delimiter, $value)) {
            return;
        }

        $assoc ??= is_array($this->subject) || $this->subject instanceof \ArrayAccess;

        Internal\Put::apply($this->subject, $path, $value, $delimiter, $assoc, $this->copy);
    }

    /**
     * Get a particular value back from the config array
     *
     * @param string $path
     * @param string $delimiter
     */
    public function remove(string $path, string $delimiter = '.'): void
    {
        if ($this->copy && !Internal\Read::exists($this->subject, $path, $delimiter)) {
            return;
        }

        Internal\Remove::apply($this->subject, $path, $delimiter, $this->copy);
    }


    /**
     * Factory method.
     *
     * @param object|array<string,mixed> $subject
     * @return static
     */
    public static function on(&$subject): self
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
    public static function onCopy($source, &$copy): self
    {
        $copy = $source;

        return new static($copy, self::COPY);
    }
}
