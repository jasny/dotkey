<?php

namespace Jasny;

/**
 * Access objects and arrays through dot notation
 */
class DotKey
{
    /**
     * @var object|array
     */
    protected $item;
    
    /**
     * Create new structure as array (when using put)
     * @var boolean
     */
    public $assoc;
    
    /**
     * Class constructor
     * 
     * @param object|array $item
     * @param boolean      $assoc  Create new structure as array (when using put)
     */
    public function __construct($item, $assoc = null)
    {
        $this->item = $item;
        $this->assoc = isset($assoc) ? $assoc : is_array($item);
    }

    
    /**
     * Check if property exists
     * 
     * @param string $key The index to fetch in dot notation
     * @return boolean
     */
    public function exists($key)
    {
        $index = explode('.', $key);
        self::getValue($this->item, $index, false, $err);
        
        return !$err;
    }
    
    /**
     * Get a value
     * 
     * @param string $key The index to fetch in dot notation
     * @return mixed
     */
    public function get($key)
    {
        $index = explode('.', $key);
        $ret = self::getValue($this->item, $index, true, $err);
        
        if ($err) {
            $invalidPath = join('.', array_slice($index, 0, -1 * $err->incomplete));
            trigger_error("Unable to get '$key': '$invalidPath' is a {$err->var}", E_USER_WARNING);
        } // @codeCoverageIgnore
        
        return $ret;
    }
    
    /**
     * Navigate through the item and get the value
     * 
     * @param array  $item
     * @param array  $index   The index sequence we are navigating to
     * @param array  $ignore  Don't raise an error if not exists
     * @param object $err     Error object [OUTPUT]
     * @return mixed
     */
    protected static function getValue($item, $index, $ignore = false, &$err = null)
    {
        $err = null;
        
        if (empty($index)) return $item;
        
        $key = array_shift($index);
        
        if ((is_array($item) || $item instanceof \Traversable) && isset($item[$key])) {
            return static::getValue($item[$key], $index, $ignore, $err);
        }
        
        if (is_object($item) && isset($item->$key)) {
            return static::getValue($item->$key, $index, $ignore, $err);
        }
        
        if ((!is_object($item) && !is_array($item)) || !$ignore) {
            $err = (object)['var' => isset($item) ? gettype($item) : null, 'incomplete' => count($index) + 1];
        }
        
        return null;
    }
    
    
    /**
     * Set a value
     * 
     * @param string $key    The index to fetch in dot notation
     * @param mixed  $value
     * @return object|array
     */
    public function set($key, $value)
    {
        $index = explode('.', $key);
        self::setValue($this->item, $index, $value, false, $err);
        
        if ($err) {
            $invalidPath = join('.', array_slice($index, 0, -1 * $err->incomplete));
            $reason = isset($err->var) ? "'$invalidPath' is a {$err->var}" : "'$invalidPath' doesn't exist";
            trigger_error("Unable to set '$key': $reason", E_USER_WARNING);
        } // @codeCoverageIgnore
        
        return $this->item;
    }
    
    /**
     * Set a value, creating the structure if required
     * 
     * @param string  $key    The index to fetch in dot notation
     * @param mixed   $value
     * @return object|array
     */
    public function put($key, $value)
    {
        $index = explode('.', $key);
        $err = null;
        
        self::setValue($this->item, $index, $value, $this->assoc ? 'array' : 'object', $err);
        
        if ($err) {
            $invalidPath = join('.', array_slice($index, 0, -1 * $err->incomplete));
            trigger_error("Unable to put '$key': '$invalidPath' is a {$err->var}", E_USER_WARNING);
        } // @codeCoverageIgnore
        
        return $this->item;
    }
    
    /**
     * Navigate through the item and set the value
     * 
     * @param array        $item
     * @param array        $index   The index sequence we are navigating to
     * @param mixed        $value
     * @param string|false $create  Create structure if required: 'object', 'array' or false
     * @param object       $err     Error object [OUTPUT]
     */
    protected static function setValue(&$item, $index, $value, $create = false, &$err = null)
    {
        $err = null;

        $key = array_shift($index);
        
        if (is_array($item) || $item instanceof \Traversable) {
            if (empty($index)) {
                $item[$key] = $value;
                return;
            }
            
            if (!isset($item[$key]) && $create) {
                $item[$key] = $create === 'array' ? [] : (object)[];
            }
            
            if (isset($item[$key])) {
                return static::setValue($item[$key], $index, $value, $create, $err);
            }
        } elseif (is_object($item)) {
            if (empty($index)) {
                $item->$key = $value;
                return;
            }
            
            if (!isset($item->$key) && $create) {
                $item->$key = $create === 'array' ? [] : (object)[];
            }
            
            if (isset($item->$key)) {
                return static::setValue($item->$key, $index, $value, $create, $err);
            }
        } else {
            $err = (object)['var' => gettype($item), 'incomplete' => count($index) + 1];
            return;
        }
        
        $err = (object)['var' => null, 'incomplete' => count($index)];
    }

    
    /**
     * Get a particular value back from the config array
     * 
     * @param string $key The index to fetch in dot notation
     * @return object|array
     */
    public function remove($key)
    {
        $index = explode('.', $key);
        self::removeValue($this->item, $index, $err);
        
        if ($err) {
            $invalidPath = join('.', array_slice($index, 0, -1 * $err->incomplete));
            trigger_error("Unable to remove '$key': '$invalidPath' is a {$err->var}", E_USER_WARNING);
        } // @codeCoverageIgnore
        
        return $this->item;
    }
    
    /**
     * Navigate through the item and remove the value
     * 
     * @param array  $item
     * @param array  $index  The index sequence we are navigating to
     * @param object $err    Error object [OUTPUT]
     * @return mixed
     */
    protected static function removeValue(&$item, $index, &$err = null)
    {
        $err = null;

        if (!is_object($item) && !is_array($item)) {
            $err = (object)['var' => gettype($item), 'incomplete' => count($index)];
            return;
        }
        
        $key = array_shift($index);
        
        if (empty($index)) {
            if (is_object($item) && isset($item->$key)) unset($item->$key);
            if (is_array($item) && isset($item[$key])) unset($item[$key]);
            return;
        }
        
        if (is_object($item) && isset($item->$key)) return static::removeValue($item->$key, $index, $err);
        if (is_array($item) && isset($item[$key])) return static::removeValue($item[$key], $index, $err);
    }

    
    /**
     * Factory method
     * 
     * @param object|array $item
     * @param boolean      $assoc  Create new structure as array (when using put)
     */
    public static function on($item, $assoc = null)
    {
        return new static($item, $assoc);
    }
}
