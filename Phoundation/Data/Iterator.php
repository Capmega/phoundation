<?php

declare(strict_types=1);

namespace Phoundation\Data;

use PDOStatement;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Exception\NotExistsException;
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
    /**
     * The list that stores all entries
     *
     * @var array $source
     */
    protected array $source = [];

    /**
     * Callback functions that, if specified, will be executed for each row in the list
     *
     * @var array $callbacks
     */
    protected array $callbacks = [];


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
     * Iterator class constructor
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null $execute
     */
    public function __construct(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null)
    {
        if (is_array($source)) {
            // This is a standard array, load it into the source
            $this->source = $source;
        } elseif (is_string($source)) {
            // This must be a query. Execute it and get a list of all entries from the result
            $this->source = sql()->list($source, $execute);

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
    }


    /**
     * Returns a new Iterator object
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null $execute
     * @return static
     */
    public static function new(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null): static
    {
        return new static($source, $execute);
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
     * @return string|float|int
     */
    public function key(): string|float|int
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
        return isset($this->source[key($this->source)]);
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
     * @param array $source
     * @return mixed
     */
    public function setSource(array $source): static
    {
        $this->source = $source;
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
        if ($exception) {
            if (!array_key_exists($key, $this->source)) {
                throw new NotExistsException(tr('The key ":key" does not exist in this object', [':key' => $key]));
            }
        }

        return isset_get($this->source[$key]);
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
     * @return mixed
     */
    public function clear(): static
    {
        $this->source = [];
        return $this;
    }


    /**
     * Deletes the specified key
     *
     * @param string|float|int $key
     * @return static
     */
    public function delete(string|float|int $key): static
    {
        unset($this->source[$key]);
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


    /**
     * Add a callback for each row
     *
     * @param callable $callback
     * @return $this
     */
    public function addItemCallback(callable $callback): static
    {
        $this->callbacks[] = $callback;
        return $this;
    }


    /**
     * Returns the row callbacks
     *
     * @return array
     */
    public function getItemCallbacks(): array
    {
        return $this->callbacks;
    }


    /**
     * Execute the specified callbacks for each row
     *
     * @return $this
     */
    public function executeItemCallbacks(): static
    {
        foreach ($this->source as $item) {
            foreach ($this->callbacks as $callback) {
                $callback($item);
            }
        }

        return $this;
    }
}