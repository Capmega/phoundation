<?php

declare(strict_types=1);

namespace Phoundation\Data\Interfaces;

use ReturnTypeWillChange;

/**
 * Interface Iterator
 *
 * This is a slightly extended interface to the default PHP iterator class. This class also requires the following
 * methods:
 *
 * - list() Returns all values with their keys in this object
 *
 * - get() Returns value for the specified key
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
interface Iterator extends \Iterator
{
    /**
     * Returns the contents of this iterator object as a JSON string
     *
     * @return string
     */
    function __toString(): string;


    /**
     * Returns the contents of this iterator object as an array
     *
     * @return array
     */
    function __toArray(): array;


    /**
     * Returns the current entry
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] function current(): mixed;

    /**
     * Progresses the internal pointer to the next button
     *
     * @return static
     */
    #[ReturnTypeWillChange] function next(): static;

    /**
     * Returns the current key for the current menu
     *
     * @return float|int|string
     */
    function key(): float|int|string;

    /**
     * Returns if the current pointer is valid or not
     *
     * @todo Is this really really required? Since we're using internal array pointers anyway, it always SHOULD be valid
     * @return bool
     */
    function valid(): bool;

    /**
     * Rewinds the internal pointer
     *
     * @return static
     */
    #[ReturnTypeWillChange] function rewind(): static;

    /**
     * Returns all values with their keys in this object
     *
     * @return array
     */
    function getList(): array;

    /**
     * Returns value for the specified key
     *
     * @param string|float|int $key
     * @param bool $exception
     * @return mixed
     */
    #[ReturnTypeWillChange] function get(string|float|int $key, bool $exception = false): mixed;

    /**
     * Returns the amount of elements contained in this object
     *
     * @return int
     */
    function getCount(): int;

    /**
     * Returns the first element contained in this object without changing the internal pointer
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] function getFirst(): mixed;


    /**
     * Returns the last element contained in this object without changing the internal pointer
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] function getLast(): mixed;

    /**
     * Clears all the internal content for this object
     *
     * @return mixed
     */
    function clear(): static;

    /**
     * Deletes the specified key
     *
     * @param float|int|string $key
     * @return mixed
     */
    function delete(string|float|int $key): static;

    /**
     * Returns if the specified field exists or not
     *
     * @param float|int|string $key
     * @return bool
     */
    function exists(float|int|string $key): bool;

    /**
     * Returns true if the list is empty
     *
     * @return bool
     */
    function isEmpty(): bool;
}