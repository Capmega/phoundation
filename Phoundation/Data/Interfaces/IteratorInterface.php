<?php

namespace Phoundation\Data\Interfaces;

use Iterator;
use PDOStatement;
use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Utils\Enums\EnumMatchMode;
use Phoundation\Utils\Utils;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;
use Phoundation\Web\Html\Components\Tables\Interfaces\HtmlDataTableInterface;
use Phoundation\Web\Html\Components\Tables\Interfaces\HtmlTableInterface;
use Stringable;

/**
 * Class IteratorCore
 *
 * This is a slightly extended interface to the default PHP iterator class. This class also requires the following
 * methods:
 *
 * - IteratorCore::getCount() Returns the number of elements contained in this object
 *
 * - IteratorCore::getFirst() Returns the first element contained in this object without changing the internal pointer
 *
 * - IteratorCore::getLast() Returns the last element contained in this object without changing the internal pointer
 *
 * - IteratorCore::clear() Clears all the internal content for this object
 *
 * - IteratorCore::delete() Deletes the specified key
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
interface IteratorInterface extends Iterator, Stringable, ArrayableInterface
{
    /**
     * Returns the class used to generate the select input
     *
     * @return string
     */
    public function getInputSelectClass(): string;


    /**
     * Sets the class used to generate the select input
     *
     * @param string $input_select_class
     *
     * @return DataList
     */
    public function setComponentClass(string $input_select_class): static;


    /**
     * Returns the current entry
     *
     * @return mixed
     */
    public function current(): mixed;


    /**
     * Progresses the internal pointer to the next entry
     *
     * @return void
     */
    public function next(): void;


    /**
     * Progresses the internal pointer to the previous entry
     *
     * @return void
     */
    public function previous(): void;


    /**
     * Returns the current key for the current button
     *
     * @return mixed
     */
    public function key(): mixed;


    /**
     * Returns if the current pointer is valid or not
     *
     * @return bool
     */
    public function valid(): bool;


    /**
     * Rewinds the internal pointer
     *
     * @return void
     */
    public function rewind(): void;


    /**
     * Sets the value for the specified key
     *
     * @note this is basically a wrapper function for IteratorCore::add($value, $key, false) that always requires a key
     *
     * @param mixed                       $value
     * @param Stringable|string|float|int $key
     *
     * @return mixed
     */
    public function set(mixed $value, Stringable|string|float|int $key): static;


    /**
     * Add the specified value to the iterator array using an optional key
     *
     * @note if no key was specified, the entry will be assigned as-if a new array entry
     *
     * @param mixed                            $value
     * @param Stringable|string|float|int|null $key
     * @param bool                             $skip_null
     * @param bool                             $exception
     *
     * @return static
     */
    public function add(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null = true, bool $exception = true): static;


    /**
     * Add the specified value to the iterator array using an optional key
     *
     * @note if no key was specified, the entry will be assigned as-if a new array entry
     *
     * @param mixed                            $value
     * @param Stringable|string|float|int|null $key
     * @param bool                             $skip_null
     * @param bool                             $exception
     *
     * @return static
     */
    public function append(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null = true, bool $exception = true): static;


    /**
     * Add the specified value to the iterator array using an optional key
     *
     * @note if no key was specified, the entry will be assigned as-if a new array entry
     *
     * @param mixed                            $value
     * @param Stringable|string|float|int|null $key
     * @param bool                             $skip_null
     * @param bool                             $exception
     *
     * @return static
     */
    public function prepend(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null = true, bool $exception = true): static;


    /**
     * Add the specified value to the iterator array using an optional key BEFORE the specified $before_key
     *
     * @note if no key was specified, the entry will be assigned as-if a new array entry
     *
     * @param mixed                            $value
     * @param Stringable|string|float|int|null $key
     * @param Stringable|string|float|int|null $before
     * @param bool                             $skip_null
     * @param bool                             $exception
     *
     * @return static
     */
    public function prependBeforeKey(mixed $value, Stringable|string|float|int|null $key = null, Stringable|string|float|int|null $before = null, bool $skip_null = true, bool $exception = true): static;


    /**
     * Add the specified value to the iterator array using an optional key AFTER the specified $after_key
     *
     * @note if no key was specified, the entry will be assigned as-if a new array entry
     *
     * @param mixed                            $value
     * @param Stringable|string|float|int|null $key
     * @param Stringable|string|float|int|null $after
     * @param bool                             $skip_null
     * @param bool                             $exception
     *
     * @return static
     */
    public function appendAfterKey(mixed $value, Stringable|string|float|int|null $key = null, Stringable|string|float|int|null $after = null, bool $skip_null = true, bool $exception = true): static;


    /**
     * Add the specified value to the iterator array using an optional key BEFORE the specified $before_value
     *
     * @note if no key was specified, the entry will be assigned as-if a new array entry
     *
     * @param mixed                            $value
     * @param Stringable|string|float|int|null $key
     * @param mixed                            $before
     * @param bool                             $strict
     * @param bool                             $skip_null
     * @param bool                             $exception
     *
     * @return static
     */
    public function prependBeforeValue(mixed $value, Stringable|string|float|int|null $key = null, mixed $before = null, bool $strict = false, bool $skip_null = true, bool $exception = true): static;


    /**
     * Add the specified value to the iterator array using an optional key AFTER the specified $after_value
     *
     * @note if no key was specified, the entry will be assigned as-if a new array entry
     *
     * @param mixed                            $value
     * @param Stringable|string|float|int|null $key
     * @param mixed                            $after
     * @param bool                             $strict
     * @param bool                             $skip_null
     * @param bool                             $exception
     *
     * @return static
     */
    public function appendAfterValue(mixed $value, Stringable|string|float|int|null $key = null, mixed $after = null, bool $strict = false, bool $skip_null = true, bool $exception = true): static;


    /**
     * Adds the specified source(s) to the internal source
     *
     * @param IteratorInterface|array|string|null $source
     *
     * @return $this
     */
    public function addSources(IteratorInterface|array|string|null $source): static;


    /**
     * Append the specified source to the end of this Iterator
     *
     * @param IteratorInterface|array ...$sources
     *
     * @return $this
     */
    public function appendSource(IteratorInterface|array ...$sources): static;


    /**
     * Prepend the specified source at the beginning of this Iterator
     *
     * @param IteratorInterface|array ...$sources
     *
     * @return $this
     */
    public function prependSource(IteratorInterface|array ...$sources): static;


    /**
     * Returns the datatype restrictions for all elements in this iterator, NULL if none
     *
     * @return array|null
     */
    public function getDataTypes(): ?array;


    /**
     * Sets the datatype restrictions for all elements in this iterator, NULL if none
     *
     * @param array|string|null $data_types
     * return static
     */
    public function setDataTypes(array|string|null $data_types): static;


    /**
     * Returns a list of all internal values with their keys
     *
     * @return mixed
     */
    public function getSource(): array;


    /**
     * Sets the internal source directly
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null                                       $execute
     *
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
     * @param bool                        $exception
     *
     * @return mixed
     */
    public function get(Stringable|string|float|int $key, bool $exception = true): mixed;


    /**
     * Returns value for the specified key, defaults that key to the specified value if it does not yet exist
     *
     * @param Stringable|string|float|int $key
     * @param mixed                       $value
     *
     * @return mixed
     */
    public function getValueOrDefault(Stringable|string|float|int $key, mixed $value): mixed;


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
    public function getFirstValue(): mixed;


    /**
     * Returns the last element contained in this object without changing the internal pointer
     *
     * @return mixed
     */
    public function getLastValue(): mixed;


    /**
     * Clears all the internal content for this object
     *
     * @return static
     */
    public function clear(): static;


    /**
     * Returns if the specified key exists or not
     *
     * @param Stringable|string|float|int $key
     *
     * @return bool
     */
    public function keyExists(Stringable|string|float|int $key): bool;


    /**
     * Returns if the specified value exists in this Iterator or not
     *
     * @note Wrapper for IteratorCore::exists()
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function valueExists(mixed $value): bool;


    /**
     * Returns if the list is empty
     *
     * @return bool
     */
    public function isEmpty(): bool;


    /**
     * Returns if the list is not empty
     *
     * @return bool
     */
    public function isNotEmpty(): bool;


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
     * @param bool        $exception
     *
     * @return int
     */
    public function getLongestValueLength(?string $key = null, bool $exception = false): int;


    /**
     * Returns the length of the shortest value
     *
     * @param string|null $key
     * @param bool        $exception
     *
     * @return int
     */
    public function getShortestValueLength(?string $key = null, bool $exception = false): int;


    /**
     * Remove source keys on the specified needles with the specified match mode
     *
     * @param Stringable|array|string|float|int $keys
     * @param EnumMatchMode                     $match_mode
     *
     * @return $this
     */
    public function removeKeys(Stringable|array|string|float|int $keys, EnumMatchMode $match_mode = EnumMatchMode::full): static;


    /**
     * Keep source keys on the specified needles with the specified match mode
     *
     * @param array|string|null $needles
     * @param EnumMatchMode     $match_mode
     *
     * @return $this
     */
    public function keepKeys(array|string|null $needles, EnumMatchMode $match_mode = EnumMatchMode::full): static;


    /**
     * Remove source values on the specified needles with the specified match mode
     *
     * @param Stringable|array|string|float|int $values
     * @param string|null                       $column
     * @param EnumMatchMode                     $match_mode
     *
     * @return $this
     */
    public function removeValues(Stringable|array|string|float|int $values, ?string $column = null, EnumMatchMode $match_mode = EnumMatchMode::full): static;


    /**
     * Deletes the entries that have columns with the specified value(s)
     *
     * @param Stringable|array|string|float|int $values
     * @param string                            $column
     *
     * @return static
     */
    public function removeValuesByColumn(Stringable|array|string|float|int $values, string $column): static;


    /**
     * Keep source values on the specified needles with the specified match mode
     *
     * @param array|string|null $needles
     * @param string|null       $column
     * @param EnumMatchMode     $match_mode
     *
     * @return $this
     */
    public function keepValues(array|string|null $needles, ?string $column = null, EnumMatchMode $match_mode = EnumMatchMode::full): static;


    /**
     * Returns the total amounts for all columns together
     *
     * @param array|string $columns
     *
     * @return array
     */
    public function getTotals(array|string $columns): array;


    /**
     * Displays a message on the command line
     *
     * @param string|null $message
     * @param bool        $header
     *
     * @return $this
     */
    public function displayCliMessage(?string $message = null, bool $header = false): static;


    /**
     * Creates and returns a CLI table for the data in this list
     *
     * @param array|string|null $columns
     * @param array             $filters
     * @param string|null       $id_column
     *
     * @return static
     */
    public function displayCliTable(array|string|null $columns = null, array $filters = [], ?string $id_column = 'id'): static;


    /**
     * Sorts the Iterator source in ascending order
     *
     * @return $this
     */
    public function sort(): static;


    /**
     * Sorts the Iterator source in descending order
     *
     * @return $this
     */
    public function rsort(): static;


    /**
     * Sorts the Iterator source keys in ascending order
     *
     * @return $this
     */
    public function ksort(): static;


    /**
     * Sorts the Iterator source keys in descending order
     *
     * @return $this
     */
    public function krsort(): static;


    /**
     * Sorts the Iterator source using the specified callback
     *
     * @return $this
     */
    public function uasort(callable $callback): static;


    /**
     * Sorts the Iterator source keys using the specified callback
     *
     * @return $this
     */
    public function uksort(callable $callback): static;


    /**
     * Will limit the amount of entries in the source of this DataList to the
     *
     * @return $this
     */
    public function limitAutoComplete(): static;


    /**
     * Returns if all (or optionally any) of the specified entries are in this list
     *
     * @param IteratorInterface|array|string $list
     * @param bool                           $all
     * @param string|null                    $always_match
     *
     * @return bool
     */
    public function containsKeys(IteratorInterface|array|string $list, bool $all = true, string $always_match = null): bool;


    /**
     * Returns a list of items that are specified, but not available in this Iterator
     *
     * @param IteratorInterface|array|string $list
     * @param string|null                    $always_match
     *
     * @return array
     * @todo Redo this with array_diff()
     */
    public function getMissingKeys(IteratorInterface|array|string $list, string $always_match = null): array;


    /**
     * Returns a list with all the keys that match the specified key
     *
     * @param array|string $needles
     * @param int          $options
     *
     * @return \Phoundation\Data\Interfaces\IteratorInterface
     */
    public function getMatchingKeys(array|string $needles, int $options = Utils::MATCH_NO_CASE | Utils::MATCH_ALL | Utils::MATCH_BEGIN | Utils::MATCH_RECURSE): IteratorInterface;


    /**
     * Returns a list with all the values that match the specified value
     *
     * @param array|string $needles
     * @param int          $options
     *
     * @return \Phoundation\Data\Interfaces\IteratorInterface
     */
    public function getMatchingValues(array|string $needles, int $options = Utils::MATCH_NO_CASE | Utils::MATCH_ALL | Utils::MATCH_BEGIN | Utils::MATCH_RECURSE): IteratorInterface;


    /**
     * Returns a list with all the values that have a sub value in the specified key that match the specified value
     *
     * @param string       $column
     * @param array|string $needles
     * @param int          $options
     *
     * @return \Phoundation\Data\Interfaces\IteratorInterface
     */
    public function getMatchingColumnValues(string $column, array|string $needles, int $options = Utils::MATCH_NO_CASE | Utils::MATCH_ALL | Utils::MATCH_BEGIN | Utils::MATCH_RECURSE): IteratorInterface;


    /**
     * Returns multiple column values for a single entry
     *
     * @param Stringable|string|float|int $key
     * @param array|string                $columns
     * @param bool                        $exception
     *
     * @return \Phoundation\Data\Interfaces\IteratorInterface
     */
    public function getSingleRowMultipleColumns(Stringable|string|float|int $key, array|string $columns, bool $exception = true): IteratorInterface;


    /**
     * Returns multiple column values for multiple entries
     *
     * @param Stringable|string|float|int $key
     * @param string                      $column
     * @param bool                        $exception
     *
     * @return mixed
     */
    public function getSingleRowsSingleColumn(Stringable|string|float|int $key, string $column, bool $exception = true): mixed;


    /**
     * Returns an array with array values containing only the specified columns
     *
     * @note This only works on sources that contains array / DataEntry object values. Any other value will cause an
     *       OutOfBoundsException
     *
     * @note If no columns were specified, then all columns will be assumed and the complete source will be returned
     *
     * @param array|string|null $columns
     *
     * @return \Phoundation\Data\Interfaces\IteratorInterface
     */
    public function getAllRowsMultipleColumns(array|string|null $columns): IteratorInterface;


    /**
     * Returns an array with each value containing a scalar with only the specified column value
     *
     * @note This only works on sources that contains array / DataEntry object values. Any other value will cause an
     *       OutOfBoundsException
     *
     * @param string $column
     *
     * @return \Phoundation\Data\Interfaces\IteratorInterface
     */
    public function getAllRowsSingleColumn(string $column): IteratorInterface;


    /**
     * Same as Arrays::splice() but for this Iterator
     *
     * @param int                     $offset
     * @param int|null                $length
     * @param IteratorInterface|array $replacement
     * @param array|null              $spliced
     *
     * @return static
     */
    public function splice(int $offset, ?int $length = null, IteratorInterface|array $replacement = [], array &$spliced = null): static;


    /**
     * Same as Arrays::spliceKey() but for this Iterator
     *
     * @param string                  $key
     * @param int|null                $length
     * @param IteratorInterface|array $replacement
     * @param bool                    $after
     * @param array|null              $spliced
     *
     * @return static
     */
    public function spliceByKey(string $key, ?int $length = null, IteratorInterface|array $replacement = [], bool $after = false, array &$spliced = null): static;


    /**
     * Renames and returns the specified value
     *
     * @param Stringable|string|float|int $key
     * @param Stringable|string|float|int $target
     * @param bool                        $exception
     *
     * @return DefinitionInterface
     */
    public function renameKey(Stringable|string|float|int $key, Stringable|string|float|int $target, bool $exception = true): mixed;


    /**
     * Creates and returns an HTML table for the data in this list
     *
     * @param array|string|null $columns
     *
     * @return HtmlTableInterface
     */
    public function getHtmlTable(array|string|null $columns = null): HtmlTableInterface;


    /**
     * Creates and returns a fancy HTML data table for the data in this list
     *
     * @param array|string|null $columns
     *
     * @return HtmlDataTableInterface
     */
    public function getHtmlDataTable(array|string|null $columns = null): HtmlDataTableInterface;


    /**
     * Returns an HTML <select> for the entries in this list
     *
     * @return InputSelectInterface
     */
    public function getHtmlSelect(): InputSelectInterface;


    /**
     * Executes the specified callback function on each
     *
     * @return $this
     */
    public function each(callable $callback): static;
}