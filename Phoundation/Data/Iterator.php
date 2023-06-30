<?php

declare(strict_types=1);

namespace Phoundation\Data;

use PDOStatement;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Traits\DataCallbacks;
use Phoundation\Data\Traits\UsesNew;
use Phoundation\Exception\NotExistsException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Json;
use ReturnTypeWillChange;
use Stringable;


/**
 * Class Iterator
 *
 * This is a slightly extended interface to the default PHP iterator class. This class also requires the following
 * methods:
 *
 * - getCount() Returns the amount of elements contained in this object
 *
 * - getFirst() Returns the first element contained in this object without changing the internal pointer
 *
 * - getLast() Returns the last element contained in this object without changing the internal pointer
 *
 * - clear() Clears all the internal content for this object
 *
 * - delete() Deletes the specified key
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
class Iterator implements IteratorInterface
{
    use UsesNew;
    use DataCallbacks;

    /**
     * The list that stores all entries
     *
     * @var array $source
     */
    protected array $source = [];


    /**
     * Returns the contents of this iterator object as a JSON string
     *
     * @return string
     */
    public function __toString(): string
    {
        return Json::encode($this->source);
    }


    /**
     * Returns the contents of this iterator object as an array
     *
     * @return array
     */
    public function __toArray(): array
    {
        return $this->source;
    }


    /**
     * Returns the current entry
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function current(): mixed
    {
        return current($this->source);
    }


    /**
     * Progresses the internal pointer to the next entry
     *
     * @return static
     */
    #[ReturnTypeWillChange] public function next(): static
    {
        next($this->source);
        return $this;
    }


    /**
     * Progresses the internal pointer to the previous entry
     *
     * @return static
     */
    #[ReturnTypeWillChange] public function previous(): static
    {
        prev($this->source);
        return $this;
    }


    /**
     * Returns the current key for the current button
     *
     * @return string|float|int|null
     */
    public function key(): string|float|int|null
    {
        return key($this->source);
    }


    /**
     * Returns if the current pointer is valid or not
     *
     * @todo Is this really really required? Since we're using internal array pointers anyway, it always SHOULD be valid
     * @return bool
     */
    public function valid(): bool
    {
        $key    = key($this->source);
        $exists = array_key_exists($key, $this->source);

        if (!$exists) {
            return false;
        }

        // The entry MIGHT exist, but we can't be 100% sure if the source array has a NULL key!
        // Weird PHP quirk coming up due to isset() / array_key_exists() not being typesafe...

        if ($key === null) {
            // key() will give NULL when the internal array pointer is out of range, great! However, this  messes up
            // when having an array with '', null or 0 because isset() and or array_key_exists() will both claim that
            // the current key exists (meaning, we're still in range) while in reality we're out of range and the key
            // doesn't exist.
            // We can't check if Iterator::source[null] exists because Iterator::source[""] will also respond to that,
            // same goes for isset() and array_key_exists()

            // current() will also return NULL when out of range, so assume that Iterator::source[null]
            // has a non-NULL value. If we're really in range, current() will give a non-NULL value, like
            // Iterator::source[null], and we'll know we're in range. If they are not equal (current() gives NULL) then
            // we're out of range.

            // However... This will still fail if some clever dipshit decides to use an array with an empty key with
            // a null value, like [null => null, 'a' => 'a'] or [null, 'a' => 'a']

            $exists = current($this->source) === $this->source[null];

            if (!$exists) {
                // Yay, the current value doesn't match the empty key value, we're out of range
                return false;
            }

            // null value, perhaps?
            if ($this->source[null] === null) {
                // Oh fork me...
                throw new OutOfBoundsException(tr('Invalid array NULL detected for empty key. Due to a PHP quirk, this value combination is NOT allowed to avoid endless loops when iterating over Iterator objects'));
            }
        }

        // We're okay!
        return true;
    }


    /**
     * Rewinds the internal pointer
     *
     * @return static
     */
    #[ReturnTypeWillChange] public function rewind(): static
    {
        reset($this->source);
        return $this;

    }


    /**
     * Add the specified value to the iterator array
     *
     * @param mixed $value
     * @param string|float|int|null $key
     * @return static
     */
    public function add(mixed $value, string|float|int|null $key = null): static
    {
        $this->source[$key] = $value;
        return $this;
    }


    /**
     * Adds the specified source to the internal source
     *
     * @param array|null $source
     * @return $this
     */
    public function addSource(?array $source): static
    {
        foreach ($source as $key => $value) {
            $this->add($key, $value);
        }

        return $this;
    }


    /**
     * Returns a list of all internal values with their keys
     *
     * @return mixed
     */
    public function getSource(): array
    {
        return $this->source;
    }


    /**
     * Sets the internal source directly
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null $execute
     * @return static
     */
    public function setSource(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null): static
    {
        if (is_array($source)) {
            // This is a standard array, load it into the source
            $this->source = $source;
        } elseif (is_string($source)) {
            // This must be a query. Execute it and get a list of all entries from the result
            $this->source = sql()->list();

        } elseif ($source instanceof PDOStatement) {
            // Get a list of all entries from the specified query PDOStatement
            $this->source = sql()->list($source);

        } elseif ($source instanceof IteratorInterface) {
            // This is another iterator object, get the data from it
            $this->source = $source->getSource();

        } else {
            // NULL was specified
            $this->source = [];
        }

        return $this;
    }


    /**
     * Returns a list of all internal definition keys
     *
     * @return mixed
     */
    public function getKeys(): array
    {
        return array_keys($this->source);
    }


    /**
     * Returns value for the specified key
     *
     * @param Stringable|string|float|int $key
     * @param bool $exception
     * @return mixed
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, bool $exception = false): mixed
    {
        if (!array_key_exists($key, $this->source)) {
            if ($exception) {
                throw new NotExistsException(tr('The key ":key" does not exist in this object', [':key' => $key]));
            }

            return null;
        }

        return $this->source[$key];
    }


    /**
     * Returns the amount of items contained in this object
     *
     * @return int
     */
    public function getCount(): int
    {
        return count($this->source);
    }


    /**
     * Returns the first element contained in this object without changing the internal pointer
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function getFirst(): mixed
    {
        return $this->source[array_key_first($this->source)];
    }


    /**
     * Returns the last element contained in this object without changing the internal pointer
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function getLast(): mixed
    {
        return $this->source[array_key_last($this->source)];
    }


    /**
     * Clears all the internal content for this object
     *
     * @return static
     */
    public function clear(): static
    {
        $this->source = [];
        return $this;
    }


    /**
     * Deletes the specified key(s)
     *
     * @param array|string|float|int $keys
     * @return static
     */
    public function delete(array|string|float|int $keys): static
    {
        if (!is_array($keys)) {
            $keys = [$keys];
        }

        foreach ($keys as $key) {
            unset($this->source[$key]);
        }

        return $this;
    }


    /**
     * Returns if the specified key exists or not
     *
     * @param Stringable|string|float|int $key
     * @return bool
     */
    public function exists(Stringable|string|float|int $key): bool
    {
        return array_key_exists((string) $key, $this->source);
    }


    /**
     * Returns if the list is empty or not
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return !count($this->source);
    }
}