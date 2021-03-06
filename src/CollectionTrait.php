<?php

namespace Harbor\Collections;

use ArrayIterator;
use DomainException;
use InvalidArgumentException;

/**
 * Trait CollectionTrait
 *  implements CollectionInterface, ArrayAccess, Countable, IteratorAggregate, JsonSerializable
 */
trait CollectionTrait
{
    /**
     * @var array
     */
    protected $items = [];

    /**
     * Checks if the container has the given key.
     * @param  string $key The key to check.
     * @return boolean
     */
    public function has($key)
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Gets the given key from the container, or returns the default if it does not
     * exist.
     * @param  string $key     The key to get.
     * @param  mixed  $default Default value to return.
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return isset($this->items[$key]) ? $this->items[$key] : $default;
    }

    /**
     * Sets the given key in the container.
     * @param mixed $key   The key to set
     * @param mixed $value The value.
     * @return $this
     */
    public function set($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($k, $v);
            }
        } else {
            $this->items[$key] = $value;
        }

        return $this;
    }

    /**
     * Push a new value onto the end of the collection.
     * @param $value
     * @return $this
     */
    public function push($value)
    {
        array_push($this->items, $value);

        return $this;
    }

    /**
     * Pop a value off the end of collection.
     * @return mixed
     */
    public function pop()
    {
        return array_pop($this->items);
    }

    /**
     * Prepend a value to the beginning of the collection.
     * @param $value
     * @return $this
     */
    public function unshift($value)
    {
        array_unshift($this->items, $value);

        return $this;
    }

    /**
     * Shift a value off the beginning of the collection.
     * @return mixed
     */
    public function shift()
    {
        return array_shift($this->items);
    }

    /**
     * Reverse the Collection.
     * @return $this
     */
    public function reverse()
    {
        $this->items = array_reverse($this->items);

        return $this;
    }

    /**
     * Returns the items as an array.
     * @return array
     */
    public function toArray()
    {
        $array = [];
        foreach ($this->items as $key => $item) {
            if (is_object($item) && method_exists($item, 'toArray')) {
                $array[$key] = $item->toArray();
            } else {
                $array[$key] = $item;
            }
        }

        return $array;
    }

    /**
     * Returns all of the items.
     * @return array
     */
    public function getAll()
    {
        return $this->items;
    }

    /**
     * Returns the data as JSON.
     * @param int $options
     * @param int $depth
     * @throws \DomainException
     * @return array
     */
    public function toJson($options = 0, $depth = 512)
    {
        $encodedData = json_encode($this->toArray(), $options, $depth);

        // @codeCoverageIgnoreStart
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return $encodedData;
            case JSON_ERROR_DEPTH:
                throw new DomainException('JSON Error: Maximum stack depth exceeded');
            case JSON_ERROR_STATE_MISMATCH:
                throw new DomainException('JSON Error: Underflow or the modes mismatch');
            case JSON_ERROR_CTRL_CHAR:
                throw new DomainException('JSON Error: Unexpected control character found');
            case JSON_ERROR_SYNTAX:
                throw new DomainException('JSON Error: Syntax error, malformed JSON');
            case JSON_ERROR_UTF8:
                throw new DomainException('JSON Error: Malformed UTF-8 characters, possibly incorrectly encoded');
            default:
                throw new DomainException('JSON Error: Unknown error');
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Removes the given key from the container.
     * @param  string $key The key to forget.
     * @return void
     */
    public function remove($key)
    {
        unset($this->items[$key]);
    }

    /**
     * Merges an array, or any object that implements a toArray method,
     * into the current data set. The data being merged in wins on conflicts.
     * @param  mixed $items
     * @return $this
     */
    public function merge($items)
    {
        if ($items instanceof CollectionInterface) {
            $items = $items->getAll();
        } elseif (is_object($items) && method_exists($items, 'toArray')) {
            $items = $items->toArray();
        } elseif (! is_array($items)) {
            throw new InvalidArgumentException('Cannot merge a value that is not an Array or an object implementing a toArray method.');
        }

        $this->items = $items + $this->items;

        return $this;
    }

    /**
     * Returns if the collection is empty or not.
     * @return bool
     */
    public function isEmpty()
    {
        return count($this->items) === 0;
    }

    /**
     * Map a callback to the items in the collection.
     * @param  callable $callback
     * @return $this
     */
    public function map(callable $callback)
    {
        foreach ($this->items as $key => $value) {
            $this->items[$key] = call_user_func($callback, $value);
        }

        return $this;
    }

    /**
     * Implements the JsonSerializable interface so it can be used with
     * json_encode().
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }


    /**
     * Call the given method on all items in the collection if the method exists.
     * @param  $name
     * @param  $args
     * @return $this
     */
    public function __call($name, $args)
    {
        foreach ($this->items as $item) {
            if (is_object($item) && method_exists($item, $name)) {
                call_user_func([$item, $name], ...$args);
            }
        }

        return $this;
    }

    /**
     * Magic method to allow object-type semantics for the container.
     * @param  string $key The key to get.
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Magic method to allow object-type semantics for the container.
     * @param  string $key   The key to set.
     * @param  mixed  $value Value to set
     * @return mixed
     */
    public function __set($key, $value)
    {
        return $this->set($key, $value);
    }

    /**
     * Magic method to empty() to work on the collection.
     * @param  string $key The key to get.
     * @return mixed
     */
    public function __isset($key)
    {
        return $this->has($key);
    }

    /**
     * IteratorAggregate: Gets an Iterator for the container.
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Countable: Gets the number of items in the container.
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * ArrayAccess: Checks if the key exists.
     * @param  string $key The key to check.
     * @return boolean
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * ArrayAccess: Unsets the given key.
     * @param  string $key The key to unset.
     * @return boolean
     */
    public function offsetUnset($key)
    {
        $this->remove($key);
    }

    /**
     * ArrayAccess: Gets the given key.
     * @param  string $key The key to get.
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * ArrayAccess: Sets the given offset.
     * @param  string $key   The key to set.
     * @param  mixed  $value The value to set.
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }
}
