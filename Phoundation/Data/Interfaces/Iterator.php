<?php

declare(strict_types=1);

namespace Phoundation\Data\Interfaces;

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
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
interface Iterator extends \Iterator
{
    /**
     * Returns the contents of this iterator object as a JSON string
     *
     * @return string
     */
    public function __toString(): string;


    /**
     * Returns the contents of this iterator object as an array
     *
     * @return array
     */
    public function __toArray(): array;


    /**
     * Returns value for the specified key
     *
     * @param string|float|int $key
     * @param bool $exception
     * @return mixed
     */
    public function get(string|float|int $key, bool $exception = false): mixed;


    /**
     * Returns if the specified field exists or not
     *
     * @param float|int|string $key
     * @return bool
     */
    public function exists(float|int|string $key): bool;

    /**
     * Returns the current key for the current menu
     *
     * @return float|int|string
     */
    public function key(): float|int|string;


    /**
     * Returns all values with their keys in this object
     *
     * @return array
     */
    public function getList(): array;


    /**
     * Returns the amount of elements contained in this object
     *
     * @return int
     */
    public function getCount(): int;


    /**
     * Returns true if the list is empty
     *
     * @return bool
     */
    public function isEmpty(): bool;


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
     * @param float|int|string $key
     * @return mixed
     */
    public function delete(string|float|int $key): static;
}