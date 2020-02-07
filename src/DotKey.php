<?php

declare(strict_types=1);

namespace Jasny\DotKey;

/**
 * Access objects and arrays through dot notation.
 */
class DotKey
{
    /**
     * @var object|array<string,mixed>
     */
    protected $subject;

    /**
     * Class constructor.
     *
     * @param object|array $subject
     */
    public function __construct(&$subject)
    {
        if (!\is_object($subject) && !\is_array($subject)) {
            $type = \gettype($subject);
            throw new \InvalidArgumentException("Subject should be an array or object; $type given");
        }
        
        $this->subject =& $subject;
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
        Internal\Write::set($this->subject, $path, $value, $delimiter);
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
        $assoc ??= is_array($this->subject) || $this->subject instanceof \ArrayAccess;

        Internal\Write::put($this->subject, $path, $value, $delimiter, $assoc);
    }

    /**
     * Get a particular value back from the config array
     *
     * @param string $path
     * @param string $delimiter
     */
    public function remove(string $path, string $delimiter = '.'): void
    {
        Internal\Remove::remove($this->subject, $path, $delimiter);
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
}
