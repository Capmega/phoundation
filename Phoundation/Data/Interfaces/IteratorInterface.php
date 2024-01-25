<?php

declare(strict_types=1);

namespace Phoundation\Data\Interfaces;

use Iterator;
use PDOStatement;
use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Utils\Utils;
use ReturnTypeWillChange;
use Stringable;


/**
 * Class Iterator
 *
 * This is a slightly extended interface to the default PHP iterator class. This class also requires the following
 * methods:
 *
 * - getCount() Returns the number of elements contained in this object
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
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * @param Stringable|string|float|int|null $key
     * @param bool $skip_null
     * @return static
     */
    public function add(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null = true): static;

    /**
     * Adds the specified source to the internal source
     *
     * @param IteratorInterface|array|string|null $source
     * @return $this
     */
    public function addSources(IteratorInterface|array|string|null $source): static;

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
     * @param array|string $needles
     * @param int $options
     * @return IteratorInterface
     */
    public function getMatchingKeys(array|string $needles, int $options = Utils::MATCH_NO_CASE | Utils::MATCH_ALL | Utils::MATCH_BEGIN | Utils::MATCH_RECURSE): IteratorInterface;

    /**
     * Returns value for the specified key
     *
     * @param Stringable|string|float|int $key
     * @param array|string $columns
     * @param bool $exception
     * @return IteratorInterface
     */
    public function getSourceKeyColumns(Stringable|string|float|int $key, array|string $columns, bool $exception = true): IteratorInterface;

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
     * Returns the number of items contained in this object
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
    public function delete(Stringable|array|string|float|int $keys): static;

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
    public function keyExists(Stringable|string|float|int $key): bool;

    /**
     * Returns if the specified value exists or not
     *
     * @param mixed $value
     * @return bool
     */
    public function valueExists(mixed $value): bool;

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

    /**
     * Returns value for the specified key, defaults that key to the specified value if it does not yet exist
     *
     * @param Stringable|string|float|int $key
     * @param mixed $value
     * @return mixed
     */
    #[ReturnTypeWillChange] public function default(Stringable|string|float|int $key, mixed $value): mixed;

    /**
     * Returns an array with array values containing only the specified columns
     *
     * @note This only works on sources that contains array / DataEntry object values. Any other value will cause an
     *       OutOfBoundsException
     *
     * @note If no columns were specified, then all columns will be assumed and the complete source will be returned
     *
     * @param array|string|null $columns
     * @return IteratorInterface
     */
    public function getSourceColumns(array|string|null $columns): IteratorInterface;

    /**
     * Returns an array with each value containing a scalar with only the specified column value
     *
     * @note This only works on sources that contains array / DataEntry object values. Any other value will cause an
     *       OutOfBoundsException
     *
     * @param string $column
     * @return IteratorInterface
     */
    public function getSourceColumn(string $column): IteratorInterface;

    /**
     * Returns the length of the longest value
     *
     * @return int
     */
    public function getLongestKeyLength(): int;

    /**
     * Returns the length of the shortest value
     *
     * @return int
     */
    public function getShortestKeyLength(): int;

    /**
     * Returns the length of the longest value
     *
     * @param string|null $key
     * @param bool $exception
     * @return int
     */
    public function getLongestValueLength(?string $key = null, bool $exception = false): int;

    /**
     * Returns the length of the shortest value
     *
     * @param string|null $key
     * @param bool $exception
     * @return int
     */
    public function getShortestValueLength(?string $key = null, bool $exception = false): int;

    /**
     * Returns the total amounts for all columns together
     *
     * @param array|string $columns
     * @return array
     */
    public function getTotals(array|string $columns): array;

    /**
     * Displays a message on the command line
     *
     * @param string|null $message
     * @param bool $header
     * @return $this
     */
    public function displayCliMessage(?string $message = null, bool $header = false): static;

    /**
     * Creates and returns a CLI table for the data in this list
     *
     * @param array|null $columns
     * @param array $filters
     * @param string|null $id_column
     * @return static
     */
    public function displayCliTable(?array $columns = null, array $filters = [], ?string $id_column = 'id'): static;
}
