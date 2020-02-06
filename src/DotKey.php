<?php

declare(strict_types=1);

namespace Jasny\DotKey;

/**
 * Access objects and arrays through dot notation.
 *
 * @template TSubject
 */
class DotKey
{
    /**
     * @var object|array<string,mixed>
     * @phpstan-var TSubject&(object|array<string,mixed>)
     */
    protected $subject;

    /**
     * Class constructor.
     *
     * @param object|array $subject
     *
     * @phpstan-param TSubject&(object|array<string,mixed>) $subject
     */
    public function __construct($subject)
    {
        if (!\is_object($subject) && !\is_array($subject)) {
            $type = \gettype($subject);
            throw new \InvalidArgumentException("Subject should be an array or object; $type given");
        }
        
        $this->subject = $subject;
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
     * @return array|object
     * @throws ResolveException
     *
     * @phpstan-return TSubject&(object|array<string,mixed>)
     */
    public function set(string $path, $value, string $delimiter = '.')
    {
        return Internal\Write::set($this->subject, $path, $value, $delimiter);
    }

    /**
     * Set a value, creating a structure if needed.
     *
     * @param string    $path
     * @param mixed     $value
     * @param string    $delimiter
     * @param bool|null $assoc     Create new structure as array. Omit to base upon subject type.
     * @return array|object
     * @throws ResolveException
     *
     * @phpstan-return TSubject&(object|array<string,mixed>)
     */
    public function put(string $path, $value, string $delimiter = '.', ?bool $assoc = null)
    {
        $assoc ??= is_array($this->subject) || $this->subject instanceof \ArrayAccess;

        return Internal\Write::put($this->subject, $path, $value, $delimiter, $assoc);
    }

    /**
     * Get a particular value back from the config array
     *
     * @param string $path
     * @param string $delimiter
     * @return object|array<string,mixed>
     *
     * @phpstan-return TSubject&(object|array<string,mixed>)
     */
    public function remove(string $path, string $delimiter = '.')
    {
        return Internal\Remove::remove($this->subject, $path, $delimiter);
    }


    
    /**
     * Factory method.
     *
     * @param object|array<string,mixed> $subject
     * @return static
     *
     * @phpstan-param TSubject&(object|array<string,mixed>) $subject
     * @phpstan-return static<TSubject>
     */
    public static function on($subject): self
    {
        return new static($subject);
    }
}
