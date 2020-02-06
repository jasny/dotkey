<?php

declare(strict_types=1);

namespace Jasny\DotKey;

use Throwable;

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
        $subject = $this->subject;
        $index = $this->splitPath($path, $delimiter);
        ;

        foreach ($index as $key) {
            if (!\is_array($subject) && !\is_object($subject)) {
                return false;
            }

            $subject = $this->descend($subject, $key, $exists, true);

            if (!$exists) {
                return false;
            }
        }
        
        return true;
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
        $subject = $this->subject;
        $index = $this->splitPath($path, $delimiter);

        while ($index !== []) {
            $key = \array_shift($index);

            if (!\is_array($subject) && !\is_object($subject)) {
                $msg = "Unable to get '$path': '%s' is of type " . \gettype($subject);
                throw $this->unresolved($msg, $path, $delimiter, $index, $key);
            }

            try {
                $subject = $this->descend($subject, $key, $exists);
            } catch (\Error $error) {
                $msg = "Unable to get '$path': error at '%s'";
                throw $this->unresolved($msg, $path, $delimiter, $index, null, $error);
            }

            if (!$exists) {
                return null;
            }
        }

        return $subject;
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
        $result = $this->subject;
        $subject =& $result;

        $index = $this->splitPath($path, $delimiter);

        while (count($index) > 1) {
            $key = \array_shift($index);

            if (!\is_array($subject) && !\is_object($subject)) {
                $msg = "Unable to set '$path': '%s' is of type " . \gettype($subject);
                throw $this->unresolved($msg, $path, $delimiter, $index, $key);
            }

            try {
                $subject =& $this->descend($subject, $key, $exists);
            } catch (\Error $error) {
                $msg = "Unable to set '$path': error at '%s'";
                throw $this->unresolved($msg, $path, $delimiter, $index, null, $error);
            }

            if (!$exists) {
                throw $this->unresolved("Unable to set '$path': '%s' doesn't exist", $path, $delimiter, $index);
            }
        }

        if (\is_array($subject) || $subject instanceof \ArrayAccess) {
            $subject[$index[0]] = $value;
        } else {
            try {
                $subject->{$index[0]} = $value;
            } catch (\Error $error) {
                throw new ResolveException("Unable to set '$path': error at '$path'", 0, $error);
            }
        }

        return $result;
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
        $result = $this->subject;
        $subject =& $result;

        $index = $this->splitPath($path, $delimiter);

        while (count($index) > 1) {
            $key = \array_shift($index);

            try {
                $subject =& $this->descend($subject, $key, $exists);
            } catch (\Error $error) {
                $msg = "Unable to put '$path': error at '%s'";
                throw $this->unresolved($msg, $path, $delimiter, $index, null, $error);
            }

            if (!$exists) {
                array_unshift($index, $key);
                break;
            }

            if (!\is_array($subject) && !\is_object($subject)) {
                break;
            }
        }

        try {
            $this->setValueCreate($subject, $index, $value, $assoc);
        } catch (\Error $error) {
            $msg = "Unable to put '$path': error at '%s'";
            throw $this->unresolved($msg, $path, $delimiter, array_slice($index, 0, -1), null, $error);
        }

        return $result;
    }


    /**
     * Create property and set the value.
     *
     * @param mixed     $subject
     * @param string[]  $index    Part or the path that doesn't exist
     * @param mixed     $value
     * @param bool|null $assoc
     */
    protected function setValueCreate(&$subject, array $index, $value, ?bool $assoc = null): void
    {
        $assoc ??= is_array($this->subject) || $this->subject instanceof \ArrayAccess;

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

    /**
     * Get a particular value back from the config array
     *
     * @return object|array<string,mixed>
     *
     * @phpstan-return TSubject&(object|array<string,mixed>)
     */
    public function remove(string $path, string $delimiter = '.')
    {
        $result = $this->subject;
        $subject =& $result;

        $index = $this->splitPath($path, $delimiter);

        while (\count($index) > 1) {
            $key = \array_shift($index);

            try {
                $subject =& $this->descend($subject, $key, $exists);
            } catch (\Error $error) {
                $msg = "Unable to remove '$path': error at '%s'";
                throw $this->unresolved($msg, $path, $delimiter, $index, null, $error);
            }

            if (!$exists) {
                return $this->subject;
            }

            if (!\is_array($subject) && !\is_object($subject)) {
                $type = \gettype($subject);
                throw $this->unresolved("Unable to remove '$path': '%s' is of type $type", $path, $delimiter, $index);
            }
        }

        try {
            $this->removeChild($subject, $index[0]);
        } catch (\Error $error) {
            $msg = "Unable to remove '$path': error at '%s'";
            throw $this->unresolved($msg, $path, $delimiter, array_slice($index, 0, -1), null, $error);
        }

        return $result;
    }

    /**
     * Remove item or property from subject.
     *
     * @param object|array<string,mixed> $subject
     * @param string                     $key
     */
    protected function removeChild(&$subject, string $key): void
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

    /**
     * Create a ResolveException, filling out %s with invalid path.
     *
     * @noinspection PhpTooManyParametersInspection
     *
     * @param string   $msg
     * @param string   $path
     * @param string   $delimiter
     * @param string[] $index
     * @param string   $key
     * @throws ResolveException
     */
    protected function unresolved(
        string $msg,
        string $path,
        string $delimiter,
        array $index,
        ?string $key = null,
        ?Throwable $previous = null
    ): ResolveException {
        $len = ($index !== [] ? \strlen($delimiter) + \strlen(\join($delimiter, $index)) : 0)
            + ($key !== null ? \strlen($delimiter) + \strlen($key) : 0);
        $invalidPath = $len > 0 ? \substr(\rtrim($path, $delimiter), 0, -1 * $len) : $path;

        return new ResolveException(sprintf($msg, $invalidPath), 0, $previous);
    }

    /**
     * Make subject a child of the subject.
     * If `$exists` is false, it wasn't possible to decent and subject is returned.
     *
     * @param object|array<string,mixed> $subject
     * @param string                     $key
     * @param mixed                      $exists      output as bool
     * @param bool                       $accessible  Check not only if property exists, but also is accessible.
     * @return mixed
     */
    protected function &descend(&$subject, string $key, &$exists, bool $accessible = false)
    {
        if (!\is_array($subject) && !$subject instanceof \ArrayAccess) {
            $exists = $accessible
                ? $this->propertyIsAccessible($subject, $key)
                : \property_exists($subject, $key);

            if ($exists) {
                $subject =& $subject->{$key};
            }
        } else {
            $exists = \is_array($subject) ? \array_key_exists($key, $subject) : $subject->offsetExists($key);
            if ($exists) {
                $subject =& $subject[$key];
            }
        }

        return $subject;
    }

    /**
     * Check if property exists and is accessible.
     *
     * @param object $object
     * @param string $property
     * @return bool
     */
    protected function propertyIsAccessible(object $object, string $property): bool
    {
        $exists = \property_exists($object, $property);

        if (!$exists || isset($object->{$property})) {
            return $exists;
        }

        try {
            $reflection = new \ReflectionProperty($object, $property);
        } catch (\ReflectionException $exception) { // @codeCoverageIgnore
            return false;                           // @codeCoverageIgnore
        }

        return $reflection->isPublic() && !$reflection->isStatic();
    }

    /**
     * Explode with trimming and check.
     * @see explode()
     *
     * @param string $path
     * @param string $delimiter
     * @return string[]
     */
    protected function splitPath(string $path, string $delimiter): array
    {
        if ($delimiter === '') {
            throw new \InvalidArgumentException("Delimiter can't be an empty string");
        }

        /** @var array<int,string> $parts */
        $parts = \explode($delimiter, trim($path, $delimiter));

        return $parts;
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
