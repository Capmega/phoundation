<?php

declare(strict_types=1);

namespace Phoundation\Data;

use PDOStatement;
use Phoundation\Cli\Cli;
use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\DataEntry\Interfaces\DataListInterface;
use Phoundation\Data\Exception\IteratorException;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Traits\DataCallbacks;
use Phoundation\Databases\Sql\Limit;
use Phoundation\Exception\NotExistsException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Enums\EnumMatchMode;
use Phoundation\Utils\Enums\Interfaces\EnumMatchModeInterface;
use Phoundation\Utils\Json;
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
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
class Iterator implements IteratorInterface
{
    use DataCallbacks;


    /**
     * The list that stores all entries
     *
     * @var array $source
     */
    protected array $source;


    /**
     * Iterator class constructor
     *
     * @param ArrayableInterface|array|null $source
     */
    public function __construct(ArrayableInterface|array|null $source = null)
    {
        if ($source) {
            $this->source = (array) $source;

        } elseif (empty($this->source)) {
            $this->source = [];
        }
    }


    /**
     * Returns a new static object
     *
     * @param array|null $source
     * @return static
     */
    public static function new(?array $source = null): static
    {
        return new static($source);
    }


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
     * Explodes the specified string into an Iterator object and returns it
     *
     * @param Stringable|string $source
     * @param string|null $separator
     * @return IteratorInterface
     */
    public static function explode(Stringable|string $source, ?string $separator = ','): IteratorInterface
    {
        $source = (string) $source;

        if ($separator) {
            $source = explode($separator, $source);

        } else {
            // We cannot explode with an empty separator, assume that $source is a single item and return it as such
            $source = [$source];
        }

        return Iterator::new()->setSource($source);
    }


    /**
     * Forces the specified source to become an Iterator
     *
     * @note DataList objects will remain DataList objects as those are extended Iterators
     * @param mixed $source
     * @param string|null $separator
     * @return IteratorInterface|DataListInterface
     */
    public static function force(mixed $source, ?string $separator = ','): IteratorInterface|DataListInterface
    {
        if (($source === '') or ($source === null)) {
            return new Iterator();
        }

        if ($source instanceof IteratorInterface) {
            // This already is an Iterator (or DataList) object
            return $source;
        }

        if (is_string($source)) {
            // Explode the string to an Iterator object, as we would to an array as well
            return static::explode($separator, $source);
        }

        if ($source instanceof ArrayableInterface) {
            // This is an object that can convert to array. Extract the array
            $source = $source->__toArray();

        } else {
            // Unknown datatype, toss it into an array
            $source = [$source];
        }

        // As of here, we have an array. Set it as an Iterator source and return
        return Iterator::new()->setSource($source);
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
    #[ReturnTypeWillChange] public function key(): string|float|int|null
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
     * Sets the value for the specified key
     *
     * @note this is basically a wrapper function for Iterator::add($value, $key, false) that always requires a key
     * @param mixed $value
     * @param Stringable|string|float|int $key
     * @return mixed
     */
    public function set(mixed $value, Stringable|string|float|int $key): static
    {
        return $this->add($value, $key, false);
    }


    /**
     * Add the specified value to the iterator array using an optional key
     *
     * @note if no key was specified, the entry will be assigned as-if a new array entry
     *
     * @param mixed $value
     * @param Stringable|string|float|int|null $key
     * @param bool $skip_null
     * @return static
     */
    public function add(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null = true): static
    {
        // Skip NULL values?
        if ($value === null) {
            if ($skip_null) {
                return $this;
            }
        }

        // NULL keys will be added as numerical "next" entries
        if ($key === null) {
            $this->source[] = $value;

        } else {
            $this->source[$key] = $value;
        }

        return $this;
    }


    /**
     * Adds the specified source(s) to the internal source
     *
     * @param IteratorInterface|array|string|null $source
     * @return $this
     */
    public function addSources(IteratorInterface|array|string|null $source): static
    {
        if ($source instanceof IteratorInterface) {
            $source = $source->getSource();
        }

        // Add each entry
        foreach (Arrays::force($source) as $key => $value) {
            $this->add($value, $key);
        }

        return $this;
    }


    /**
     * Merge the specified Iterator or array into this Iterator
     *
     * @param IteratorInterface|array ...$sources
     * @return static
     */
    public function merge(IteratorInterface|array ...$sources): static
    {
        foreach ($sources as $source) {
            if ($source instanceof IteratorInterface) {
                $source = $source->getSource();
            }

            $this->source = array_merge($this->source, $source);
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
     * Validate that the requested column exists
     *
     * @param mixed $value
     * @param array|string $columns
     * @return array
     */
    protected function validateValue(mixed $value, array|string $columns): array
    {
        // Ensure we have arrays
        if (is_object($value)) {
            if (!$value instanceof ArrayableInterface) {
                throw new OutOfBoundsException(tr('Cannot get source columns for ":this", the source contains non arrayable objects', [
                    ':this' => get_class($this)
                ]));
            }

            $value = $value->__toArray();
        }

        foreach (Arrays::force($columns) as $column) {
            if (!array_key_exists($column, $value)) {
                throw new OutOfBoundsException(tr('The requested column ":column" does not exist', [
                    ':column' => $column
                ]));
            }
        }

        return $value;
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
     * Returns a list of all internal definition keys with their indices (positions within the array)
     *
     * @return mixed
     */
    public function getKeyIndices(): array
    {
        return array_flip(array_keys($this->source));
    }


    /**
     * Returns value for the specified key
     *
     * @param Stringable|string|float|int $key
     * @param bool $exception
     * @return mixed
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, bool $exception = true): mixed
    {
        if (!array_key_exists($key, $this->source)) {
            if ($exception) {
                throw new NotExistsException(tr('The key ":key" does not exist in this ":class" object', [
                    ':key'   => $key,
                    ':class' => get_class($this)
                ]));
            }

            return null;
        }

        return $this->source[$key];
    }


    /**
     * Returns value for the specified key, defaults that key to the specified value if it does not yet exist
     *
     * @param Stringable|string|float|int $key
     * @param mixed $value
     * @return mixed
     */
    #[ReturnTypeWillChange] public function default(Stringable|string|float|int $key, mixed $value): mixed
    {
        if (!array_key_exists($key, $this->source)) {
            $this->source[$key] = $value;
        }

        return $this->source[$key];
    }


    /**
     * Returns the number of items contained in this object
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
     * Returns if the specified key exists or not
     *
     * @param Stringable|string|float|int $key
     * @return bool
     */
    public function keyExists(Stringable|string|float|int $key): bool
    {
        return array_key_exists($key, $this->source);
    }


    /**
     * Returns if the specified value exists in this Iterator or not
     *
     * @note Wrapper for Iterator::exists()
     * @param mixed $value
     * @return bool
     */
    public function valueExists(mixed $value): bool
    {
        return static::exists($value);
    }


    /**
     * Returns if the specified value exists in this Iterator or not
     *
     * @param mixed $value
     * @return bool
     */
    public function exists(mixed $value): bool
    {
        return in_array($value, $this->source);
    }


    /**
     * Returns if the list is empty
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return !count($this->source);
    }


    /**
     * Returns if the list is not empty
     *
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return (bool) count($this->source);
    }


    /**
     * Returns the length of the longest value
     *
     * @return int
     */
    public function getLongestKeyLength(): int
    {
        return Arrays::getLongestKeyLength($this->source);
    }


    /**
     * Returns the length of the shortest value
     *
     * @return int
     */
    public function getShortestKeyLength(): int
    {
        return Arrays::getShortestKeyLength($this->source);
    }


    /**
     * Returns the length of the longest value
     *
     * @param string|null $key
     * @param bool $exception
     * @return int
     */
    public function getLongestValueLength(?string $key = null, bool $exception = false): int
    {
        return Arrays::getLongestValueLength($this->source, $key, $exception);
    }


    /**
     * Returns the length of the shortest value
     *
     * @param string|null $key
     * @param bool $exception
     * @return int
     */
    public function getShortestValueLength(?string $key = null, bool $exception = false): int
    {
        return Arrays::getShortestValueLength($this->source, $key, $exception);
    }


    /**
     * Deletes the specified key(s)
     *
     * @param Stringable|array|string|float|int $keys
     * @return static
     */
    public function delete(Stringable|array|string|float|int $keys): static
    {
        foreach (Arrays::force($keys, null) as $key) {
            unset($this->source[$key]);
        }

        return $this;
    }


    /**
     * Deletes the specified value(s)
     *
     * @param Stringable|array|string|float|int $values
     * @param bool $strict
     * @return static
     */
    public function deleteByValues(Stringable|array|string|float|int $values, bool $strict = true): static
    {
        foreach (Arrays::force($values, null) as $value) {
            $key = array_search($value, $this->source, $strict);

            if ($key !== false) {
                unset($this->source[$key]);
            }
        }

        return $this;
    }


    /**
     * Deletes the entries that have columns with the specified value(s)
     *
     * @param Stringable|array|string|float|int $values
     * @param string $column
     * @return static
     */
    public function deleteByColumnValues(Stringable|array|string|float|int $values, string $column): static
    {
        foreach (Arrays::force($values, null) as $value) {
            foreach ($this->source as $key => $data) {
                if (is_array($data)) {
                    if (!array_key_exists($column, $data)) {
                        throw new OutOfBoundsException(tr('Cannot delete entries by column ":column" value ":value" because entry ":key" does not have the requested column ":column"', [
                            ':key'    => $key,
                            ':value'  => $value,
                            ':column' => $column
                        ]));
                    }

                    if ($data[$key] === $value) {
                        unset($this->source[$key]);
                    }
                } else {
                    if (!$data instanceof DataEntry) {
                        throw new OutOfBoundsException(tr('Cannot delete entries by column ":column" value ":value" because key ":key" is neither array nor DataEntry', [
                            ':key'    => $key,
                            ':value'  => $value,
                            ':column' => $column
                        ]));
                    }

                    // This entry is not an array but DataEntry object. Compare using DataEntry::getSourceValue()
                    if ($data->getSourceValue($key) === $value) {
                        unset($this->source[$key]);
                    }
                }
            }
        }

        return $this;
    }


    /**
     * Keep source keys on the specified needles with the specified match mode
     *
     * @param array|string|null $needles
     * @param EnumMatchModeInterface $match_mode
     * @return $this
     */
    public function keepKeys(array|string|null $needles, EnumMatchModeInterface $match_mode = EnumMatchMode::full): static
    {
        $this->source = Arrays::keepKeys($this->source, $needles, $match_mode);
        return $this;
    }


    /**
     * Remove source keys on the specified needles with the specified match mode
     *
     * @param array|string|null $needles
     * @param EnumMatchModeInterface $match_mode
     * @return $this
     */
    public function removeKeys(array|string|null $needles, EnumMatchModeInterface $match_mode = EnumMatchMode::full): static
    {
        $this->source = Arrays::removeKeys($this->source, $needles, $match_mode);
        return $this;
    }


    /**
     * Keep source values on the specified needles with the specified match mode
     *
     * @param array|string|null $needles
     * @param string|null $column
     * @param EnumMatchModeInterface $match_mode
     * @return $this
     */
    public function keepValues(array|string|null $needles, ?string $column = null, EnumMatchModeInterface $match_mode = EnumMatchMode::full): static
    {
        $this->source = Arrays::keepValues($this->source, $needles, $column, $match_mode);
        return $this;
    }


    /**
     * Remove source values on the specified needles with the specified match mode
     *
     * @param array|string|null $needles
     * @param string|null $column
     * @param EnumMatchModeInterface $match_mode
     * @return $this
     */
    public function removeValues(array|string|null $needles, ?string $column = null, EnumMatchModeInterface $match_mode = EnumMatchMode::full): static
    {
        $this->source = Arrays::removeValues($this->source, $needles, $column, $match_mode);
        return $this;
    }


    /**
     * Returns the total amounts for all columns together
     *
     * @param array|string $columns
     * @return array
     */
    public function getTotals(array|string $columns): array
    {
        $columns = Arrays::force($columns);
        $return  = [tr('Totals')];

        foreach ($this->source as &$entry) {
            if (!is_array($entry)) {
                throw new OutOfBoundsException(tr('Cannot generate source totals, source contains non-array entry ":entry"', [
                    ':entry' => $entry
                ]));
            }

            foreach ($columns as $column => $total) {
                if (!array_key_exists($column, $entry)) {
                    continue;
                }

                // Get data from array
                if ($total) {
                    if (array_key_exists($column, $return)) {
                        $return[$column] += $entry[$column];

                    } else {
                        $return[$column]  = $entry[$column];
                    }

                } else {
                    $return[$column]  = null;
                }
            }
        }

        return $return;
    }


    /**
     * Displays a message on the command line
     *
     * @param string|null $message
     * @param bool $header
     * @return $this
     */
    public function displayCliMessage(?string $message = null, bool $header = false): static
    {
        if ($header) {
            Log::information($message, use_prefix: false);

        } else {
            Log::cli($message);
        }

        return $this;
    }


    /**
     * Creates and returns a CLI table for the data in this list
     *
     * @param array|string|null $columns
     * @param array $filters
     * @param string|null $id_column
     * @return static
     */
    public function displayCliTable(array|string|null $columns = null, array $filters = [], ?string $id_column = 'id'): static
    {
        Cli::displayTable($this->source, $columns, $id_column);
        return $this;
    }


    /**
     * Sorts the Iterator source in ascending order
     *
     * @return $this
     */
    public function sort(): static
    {
        asort($this->source);
        return $this;
    }


    /**
     * Sorts the Iterator source in descending order
     *
     * @return $this
     */
    public function rsort(): static
    {
        arsort($this->source);
        return $this;
    }



    /**
     * Sorts the Iterator source keys in ascending order
     *
     * @return $this
     */
    public function ksort(): static
    {
        ksort($this->source);
        return $this;
    }


    /**
     * Sorts the Iterator source keys in descending order
     *
     * @return $this
     */
    public function krsort(): static
    {
        krsort($this->source);
        return $this;
    }



    /**
     * Sorts the Iterator source using the specified callback
     *
     * @return $this
     */
    public function uasort(callable $callback): static
    {
        uasort($this->source, $callback);
        return $this;
    }


    /**
     * Sorts the Iterator source keys using the specified callback
     *
     * @return $this
     */
    public function uksort(callable $callback): static
    {
        uksort($this->source, $callback);
        return $this;
    }


    /**
     * Will limit the amount of entries in the source of this DataList to the
     *
     * @return $this
     */
    public function limitAutoComplete(): static
    {
        $this->source = Arrays::limit($this->source, Limit::shellAutoCompletion());
        return $this;
    }


    /**
     * Returns a list of items that are specified, but not available in this Iterator
     *
     * @todo Redo this with array_diff()
     * @param IteratorInterface|array|string $list
     * @param string|null $always_match
     * @return array
     */
    public function getMissingKeys(IteratorInterface|array|string $list, string $always_match = null): array
    {
        $return = [];

        foreach (Arrays::force($list) as $key) {
            if (array_key_exists($key, $this->source)) {
                continue;
            }

            // Can still match if $always_match is available!
            if ($always_match and array_key_exists($always_match, $this->source)) {
                // Okay, this list contains ALL the requested entries due to $always_match
                return [];
            }

            $return[] = $key;
        }

        return $return;
    }


    /**
     * Returns if all (or optionally any) of the specified entries are in this list
     *
     * @param IteratorInterface|array|string $list
     * @param bool $all
     * @param string|null $always_match
     * @return bool
     */
    public function containsKeys(IteratorInterface|array|string $list, bool $all = true, string $always_match = null): bool
    {
        foreach (Arrays::force($list) as $key) {
            if (!array_key_exists($key, $this->source)) {
                if ($all) {
                    // All need to be in the array, but we found one missing.
                    // Can still match if $always_match is available!
                    if ($always_match and array_key_exists($always_match, $this->source)) {
                        // Okay, this list contains ALL the requested entries due to $always_match
                        return true;
                    }

                    return false;
                }

            } elseif (!$all) {
                // only one needs to be in the array, we found one, we're good!
                return true;
            }
        }

        // All were in the array
        return true;
    }


    /**
     * Returns a list with all the keys that match the specified key
     *
     * @param array|string $needles
     * @param int $options
     * @return IteratorInterface
     */
    public function getMatchingKeys(array|string $needles, int $options = Utils::MATCH_NO_CASE | Utils::MATCH_ALL | Utils::MATCH_BEGIN | Utils::MATCH_RECURSE): IteratorInterface
    {
        return new Iterator(Arrays::getMatches($this->getKeys(), $needles, $options));
    }


    /**
     * Returns a list with all the values that match the specified value
     *
     * @param array|string $needles
     * @param int $options
     * @return IteratorInterface
     */
    public function getMatchingValues(array|string $needles, int $options = Utils::MATCH_NO_CASE | Utils::MATCH_ALL | Utils::MATCH_BEGIN | Utils::MATCH_RECURSE): IteratorInterface
    {
        return new Iterator(Arrays::getMatches($this->source, $needles, $options));
    }


    /**
     * Returns a list with all the values that have a sub value in the specified key that match the specified value
     *
     * @param string $key
     * @param array|string $needles
     * @param int $options
     * @return IteratorInterface
     */
    public function getMatchingSubValues(string $key, array|string $needles, int $options = Utils::MATCH_NO_CASE | Utils::MATCH_ALL | Utils::MATCH_BEGIN | Utils::MATCH_RECURSE): IteratorInterface
    {
        return new Iterator(Arrays::getSubMatches($this->source, $needles, $key, $options));
    }


    /**
     * Returns multiple column values for a single entry
     *
     * @param Stringable|string|float|int $key
     * @param array|string $columns
     * @param bool $exception
     * @return IteratorInterface
     */
    #[ReturnTypeWillChange] public function getSingleRowMultipleColumns(Stringable|string|float|int $key, array|string $columns, bool $exception = true): IteratorInterface
    {
        if (!$columns) {
            throw new OutOfBoundsException(tr('Cannot return source key columns for ":this", no columns specified', [
                ':this' => get_class($this)
            ]));
        }

        $value = $this->get($key, $exception);
        $value = $this->validateValue($value, $columns);

        return new Iterator(Arrays::keepKeys($value, $columns));
    }


    /**
     * Returns multiple column values for multiple entries
     *
     * @param Stringable|string|float|int $key
     * @param string $column
     * @param bool $exception
     * @return mixed
     */
    #[ReturnTypeWillChange] public function getSingleRowsSingleColumn(Stringable|string|float|int $key, string $column, bool $exception = true): mixed
    {
        if (!$column) {
            throw new OutOfBoundsException(tr('Cannot return source key column for ":this", no column specified', [
                ':this' => get_class($this)
            ]));
        }

        $value = $this->get($key, $exception);
        $value = $this->validateValue($value, $column);
        $value = Arrays::keepKeys($value, $column);

        return $value[$column];
    }


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
    public function getAllRowsMultipleColumns(array|string|null $columns): IteratorInterface
    {
        if (!$columns) {
            // Return all columns
            return new Iterator($this->source);
        }

        // Already ensure columns is an array here to avoid Arrays::keep() having to convert all the time, just in case.
        $return  = [];
        $columns = Arrays::force($columns);

        foreach ($this->source as $key => $value) {
            $value        = $this->validateValue($value, $columns);
            $return[$key] = Arrays::keepKeys($value, $columns);
        }

        return new Iterator($return);
    }


    /**
     * Returns an array with each value containing a scalar with only the specified column value
     *
     * @note This only works on sources that contains array / DataEntry object values. Any other value will cause an
     *       OutOfBoundsException
     *
     * @param string $column
     * @return IteratorInterface
     */
    public function getAllRowsSingleColumn(string $column): IteratorInterface
    {
        if (!$column) {
            throw new OutOfBoundsException(tr('Cannot return source column for ":this", no column specified', [
                ':this' => get_class($this)
            ]));
        }

        $return = [];

        foreach ($this->source as $key => $value) {
            $value        = $this->validateValue($value, $column);
            $return[$key] = $value[$column];
        }

        return new Iterator($return);
    }





// TODO DEPRECATED
    /**
     * Returns value for the specified key
     *
     * @param Stringable|string|float|int $key
     * @param bool $exception
     * @return mixed
     */
    #[ReturnTypeWillChange] public function getSourceValue(Stringable|string|float|int $key, bool $exception = true): mixed
    {
        return $this->get($key, $exception);
    }


    /**
     * Sets the value for the specified key
     *
     * @param mixed $value
     * @param Stringable|string|float|int $key
     * @param bool $exception
     * @return static
     */
    #[ReturnTypeWillChange] public function setSourceValue(mixed $value, Stringable|string|float|int $key): static
    {
        return $this->set($value, $key);
    }


    /**
     * Same as Arrays::splice() but for this Iterator
     *
     * @param int $offset
     * @param int|null $length
     * @param IteratorInterface|array $replacement
     * @param array|null $spliced
     * @return static
     */
    public function splice(int $offset, ?int $length = null, IteratorInterface|array $replacement = [], array &$spliced = null): static
    {
        $spliced = Arrays::splice($this->source, $offset, $length, $replacement);
        return $this;
    }


    /**
     * Same as Arrays::spliceKey() but for this Iterator
     *
     * @param string $key
     * @param int|null $length
     * @param IteratorInterface|array $replacement
     * @param bool $after
     * @param array|null $spliced
     * @return static
     */
    public function spliceByKey(string $key, ?int $length = null, IteratorInterface|array $replacement = [], bool $after = false, array &$spliced = null): static
    {
        $spliced = Arrays::spliceByKey($this->source, $key, $length, $replacement, $after);
        return $this;
    }


    /**
     * Renames and returns the specified column
     *
     * @param Stringable|string|float|int $key
     * @param Stringable|string|float|int $target
     * @param bool $exception
     * @return DefinitionInterface
     */
    #[ReturnTypeWillChange] public function rename(Stringable|string|float|int $key, Stringable|string|float|int $target, bool $exception = true): mixed
    {
        // First, ensure the target doesn't exist yet!
        if (array_key_exists($target, $this->source)) {
            throw new IteratorException(tr('Cannot rename key ":key" to target ":target", the target key already exists', [
                ':key'    => $key,
                ':target' => $target,
            ]));
        }

        // Then, get the definition
        $entry = $this->get($key, $exception);

        // Now rename
        $this->source[$target] = $this->source[$key];

        // Done, return!
        return $entry;
    }
}
