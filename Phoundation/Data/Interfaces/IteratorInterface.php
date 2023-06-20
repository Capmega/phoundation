<?php

namespace Phoundation\Data\Interfaces;

use PDOStatement;
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
interface IteratorInterface extends \Iterator, Stringable
{
    /**
     * Return the object contents in JSON string format
     *
     * @return string
     */
    function __toString(): string;

    /**
     * Return the object contents in array format
     *
     * @return array
     */
    function __toArray(): array;

    /**
     * Iterator class constructor
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null $execute
     */
    public function __construct(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null);

    /**
     * Returns a new Iterator object
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null $execute
     * @return static
     */
    public static function new(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null): static;

    /**
     * Returns the current entry
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function current(): mixed;

    /**
     * Progresses the internal pointer to the next entry
     *
     * @return static
     */
    #[ReturnTypeWillChange] public function next(): static;

    /**
     * Progresses the internal pointer to the previous entry
     *
     * @return static
     */
    #[ReturnTypeWillChange] public function previous(): static;

    /**
     * Returns the current key for the current button
     *
     * @return string|float|int
     */
    public function key(): string|float|int;

    /**
     * Returns if the current pointer is valid or not
     *
     * @todo Is this really really required? Since we're using internal array pointers anyway, it always SHOULD be valid
     * @return bool
     */
    public function valid(): bool;

    /**
     * Rewinds the internal pointer
     *
     * @return static
     */
    #[ReturnTypeWillChange] public function rewind(): static;

    /**
     * Returns a list of all internal values with their keys
     *
     * @return mixed
     */
    public function getSource(): array;

    /**
     * Sets the internal source directly
     *
     * @param array $source
     * @return mixed
     */
    public function setSource(array $source): static;

    /**
     * Returns value for the specified key
     *
     * @param Stringable|string|float|int $key
     * @param bool $exception
     * @return mixed
     */
    public function get(Stringable|string|float|int $key, bool $exception = false): mixed;

    /**
     * Returns the amount of items contained in this object
     *
     * @return int
     */
    public function getCount(): int;

    /**
     * Returns the first element contained in this object without changing the internal pointer
     *
     * @return mixed
     */
    public function getFirst(): mixed;

    /**
     * Returns the last element contained in this object without changing the internal pointer
     *
     * @return mixed
     */
    public function getLast(): mixed;

    /**
     * Clears all the internal content for this object
     *
     * @return mixed
     */
    public function clear(): static;

    /**
     * Deletes the specified key
     *
     * @param string|float|int $key
     * @return static
     */
    public function delete(string|float|int $key): static;

    /**
     * Returns if the specified key exists or not
     *
     * @param Stringable|string|float|int $key
     * @return bool
     */
    public function exists(Stringable|string|float|int $key): bool;

    /**
     * Returns if the list is empty or not
     *
     * @return bool
     */
    public function isEmpty(): bool;

    /**
     * Execute the specified callback for each row
     *
     * @param callable $callback
     * @return $this
     */
    public function addItemCallback(callable $callback): static;

    /**
     * Returns the row callbacks
     *
     * @return array
     */
    public function getItemCallbacks(): array;

    /**
     * Execute the specified callbacks for each row
     *
     * @return $this
     */
    public function executeItemCallbacks(): static;
}