<?php

namespace Phoundation\Data\Interfaces;

use Iterator;
use PDOStatement;
use Phoundation\Core\Interfaces\ArrayableInterface;
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
interface IteratorInterface extends Iterator, Stringable, ArrayableInterface
{
    /**
     * Returns the current entry
     *
     * @return mixed
     */
    public function current(): mixed;

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
     * @return string|float|int|null
     */
    #[ReturnTypeWillChange] public function key(): string|float|int|null;

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
     * Add the specified value to the iterator array
     *
     * @param mixed $value
     * @param string|float|int|null $key
     * @param bool $skip_null
     * @return static
     */
    public function add(mixed $value, string|float|int|null $key = null, bool $skip_null = true): static;

    /**
     * Adds the specified source to the internal source
     *
     * @param array|null $source
     * @return $this
     */
    public function addSource(?array $source): static;

    /**
     * Returns a list of all internal values with their keys
     *
     * @return mixed
     */
    public function getSource(): array;

    /**
     * Returns value for the specified key
     *
     * @param Stringable|string|float|int $key
     * @param bool $exception
     * @return mixed
     */
    public function getSourceKey(Stringable|string|float|int $key, bool $exception = true): mixed;

    /**
     * Sets value for the specified key
     *
     * @note This is a wrapper for Iterator::set()
     * @param mixed $value
     * @param Stringable|string|float|int $key
     * @return mixed
     */
    public function set(mixed $value, Stringable|string|float|int $key): static;

    /**
     * Returns a list of items that are specified, but not available in this Iterator
     *
     * @param IteratorInterface|array|string $list
     * @param string|null $always_match
     * @return array
     */
    public function getMissingKeys(IteratorInterface|array|string $list, string $always_match = null): array;

    /**
     * Returns if all (or optionally any) of the specified entries are in this list
     *
     * @param IteratorInterface|array|string $list
     * @param bool $all
     * @param string|null $always_match
     * @return bool
     */
    public function containsKeys(IteratorInterface|array|string $list, bool $all = true, string $always_match = null): bool;

    /**
     * Returns a list with all the keys that match the specified key
     *
     * @param array|string $keys
     * @return array
     */
    public function getMatchingKeys(array|string $keys): array;

    /**
     * Returns value for the specified key
     *
     * @param Stringable|string|float|int $key
     * @param array|string $columns
     * @param bool $exception
     * @return mixed
     */
    public function getSourceKeyColumns(Stringable|string|float|int $key, array|string $columns, bool $exception = true): mixed;

    /**
     * Returns value for the specified key
     *
     * @param Stringable|string|float|int $key
     * @param string $column
     * @param bool $exception
     * @return mixed
     */
    public function getSourceKeyColumn(Stringable|string|float|int $key, string $column, bool $exception = true): mixed;

    /**
     * Sets the internal source directly
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null $execute
     * @return static
     */
    public function setSource(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null): static;

    /**
     * Returns a list of all internal definition keys
     *
     * @return mixed
     */
    public function getKeys(): array;

    /**
     * Returns a list of all internal definition keys with their indices (positions within the array)
     *
     * @return mixed
     */
    public function getKeyIndices(): array;

    /**
     * Returns value for the specified key
     *
     * @param Stringable|string|float|int $key
     * @param bool $exception
     * @return mixed
     */
    public function get(Stringable|string|float|int $key, bool $exception = true): mixed;

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
     * @return static
     */
    public function clear(): static;

    /**
     * Deletes the specified key(s)
     *
     * @param Stringable|array|string|float|int $keys
     * @return static
     */
    public function deleteEntries(Stringable|array|string|float|int $keys): static;

    /**
     * Deletes the entries that have columns with the specified value(s)
     *
     * @param Stringable|array|string|float|int $values
     * @param string $column
     * @return static
     */
    public function deleteByColumnValue(Stringable|array|string|float|int $values, string $column): static;

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
     * Merge the specified Iterator or array into this Iterator
     *
     * @param IteratorInterface|array $source
     * @return static
     */
    public function merge(IteratorInterface|array $source): static;
}
