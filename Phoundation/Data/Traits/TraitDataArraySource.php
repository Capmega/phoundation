<?php

/**
 * Trait TraitDataSourceArray
 *
 * This trait contains the basic methods required to use a source array
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use PDOStatement;
use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Data\DataEntries\Exception\DataEntryBadException;
use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Data\Interfaces\ArraySourceInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Exception\NotExistsException;
use Phoundation\Utils\Arrays;
use ReturnTypeWillChange;
use Stringable;


trait TraitDataArraySource
{
    use TraitMethodsPoad;


    /**
     * The source to use
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
        return $this->getPoadString();
    }


    /**
     * Returns the source data when cast to array in POAD (Phoundation Object Array Data) format. This format allows any
     * object to be recreated from this array
     *
     * POA structures must have the following format
     * [
     *     "datatype" => The phoundation version that created this array
     *     "datatype" => "object"
     *     "class"    => The class name (static::class should suffice)
     *     "source"   => The object's source data
     * ]
     *
     * @return array
     */
    public function __toArray(): array
    {
        return $this->getPoadArray();
    }


    /**
     * Returns a new DataEntry object from the specified array source
     *
     * @param ArraySourceInterface|array|string|null $source
     *
     * @return TraitDataArraySource|null
     */
    public static function newFromSourceOrNull(ArraySourceInterface|array|string|null $source): ?static
    {
        if ($source === null) {
            return null;
        }

        return static::newFromSource($source);
    }


    /**
     * Returns a new DataEntry object from the specified array source
     *
     * @param DataEntryInterface|IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null                                                          $execute
     *
     * @return static
     */
    public static function newFromSource(DataEntryInterface|IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null): static
    {
        if ($source instanceof ArraySourceInterface) {
            if (!is_a($source, static::class)) {
                throw new DataEntryBadException(
                    tr('The specified source ":source" must be either an array or an instance of ":static"', [
                        ':static' => static::class,
                        ':source' => $source::class,
                    ])
                );
            }

            $source = $source->getSource();
        }

        $entry = new static(null);
        return $entry->setSource($source, $execute);
    }


    /**
     * Returns the source
     *
     * @return array
     */
    public function getSource(): array
    {
        return $this->source;
    }


    /**
     * Returns the source without processing any data first
     *
     * @return array
     */
    public function getSourceUnprocessed(): array
    {
        return $this->source;
    }


    /**
     * Returns the source with the array keys re-indexed, starting from 0
     *
     * @note: The arrays returned from this method will no longer contain key information that are linked to the value.
     *        Instead, the keys will be linear numbers going up, starting from 0.
     *
     * @note: PHP arrays can either be real arrays (with index keystarting from 0, continuing linearly) or (anything
     *        else) hash maps. Most PHP arrays are hash maps because they don't contain "just a list of values", they
     *        contain a key => value list. These hash maps work exactly the same as normal arrays in PHP and in 99.9%
     *        of the cases, you won't notice the difference. Json::encode(), however, WILL notice the difference. A PHP
     *        array will be JSON encoded as "[item, item, ...]" and may be required for many JavaScript applications.
     *        Hash maps, on the other hand, will be JSON encoded as "{key: item, key: item, ...}". Almost all
     *        Phoundation Iterator objects will contain sources that are in reality hash maps, and as such, will render
     *        in JSON as "{ ... }" which may cause issues in JavaScript applications. For those instances, use source
     *        arrays not from Iterator::getSource() but from this Iterator::getSourceReindexed() which will return a
     *        true array with keys starting from 0.
     *
     * @note  Just to be very clear: A PHP array that contains a single entry where the key was specified is a hash map.
     *        An array that was created like $a = ["a", "b", "c"] is an actual array. A PHP array that was created like
     *        $a = ["a", "b", "c", 3 => "d"] is a hash map, even though the fourth entry has the key 3, like a normal
     *        array would!
     *
     * @return array
     */
    public function getSourceReIndexed(): array
    {
        return Arrays::reindex($this->source);
    }


    /**
     * Returns a list of all internal definition keys
     *
     * @return mixed
     */
    public function getSourceKeys(): array
    {
        return array_keys($this->getSource());
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
     * Sets the source data for this object
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null
     * @param array|null                                       $execute
     *
     * @return static
     */
    public function setSource(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null): static
    {
        $this->source = Arrays::extractSourceArray($source, $execute);
        return $this;
    }


    /**
     * Returns true if this source contains a single entry
     *
     * @return bool
     */
    public function hasSingleEntry(): bool
    {
        return count($this->source) === 1;
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
     * Returns the number of items contained in this object
     *
     * Wrapper for IteratorCore::getCount()
     *
     * @return int
     */
    public function count(): int
    {
        return $this->getCount();
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
     * Returns value for the specified key
     *
     * @param Stringable|string|float|int $key
     * @param mixed                       $default
     * @param bool|null                   $exception
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, mixed $default = null, ?bool $exception = null): mixed
    {
        return array_get_safe($this->source, $key, $default, $exception);
    }


    /**
     * Sets the value for the specified key
     *
     * @param mixed                       $value
     * @param Stringable|string|float|int $key
     * @param bool                        $skip_null_values
     *
     * @return static
     */
    public function set(mixed $value, Stringable|string|float|int $key, bool $skip_null_values = true): static
    {
        if ($value === null) {
            if ($skip_null_values) {
                return $this;
            }
        }

        $this->source[$key] = $value;
        return $this;
    }


    /**
     * Returns the random entry
     *
     * @return Stringable|string|int|null
     */
    #[ReturnTypeWillChange] public function getRandomKey(): Stringable|string|int|null
    {
        if (empty($this->source)) {
            return null;
        }

        return array_rand($this->source, 1);
    }


    /**
     * Returns a random entry
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function getRandom(): mixed
    {
        if (empty($this->source)) {
            return null;
        }

        return array_rand($this->source, 1);
    }


    /**
     * Keep source keys on the specified needles with the specified match mode
     *
     * @param ArrayableInterface|Stringable|array|string|int|null $needles
     * @param bool                                     $strict
     *
     * @return static
     */
    public function keepKeys(ArrayableInterface|Stringable|array|string|int|null $needles, bool $strict = false): static
    {
        $this->source = Arrays::keepKeys($this->source, $needles, $strict);
        return $this;
    }


    /**
     * Remove source keys on the specified needles with the specified match mode
     *
     * @param Stringable|array|string|int $keys
     * @param bool                        $strict
     *
     * @return static
     */
    public function removeKeys(Stringable|array|string|int $keys, bool $strict = false): static
    {
        $this->source = Arrays::removeKeys($this->source, $keys, $strict);
        return $this;
    }


    /**
     * Keep source values on the specified needles with the specified match mode
     *
     * @param ArrayableInterface|Stringable|array|string|int|null $needles
     * @param string|null                              $column
     * @param bool                                     $strict
     *
     * @return static
     */
    public function keepValues(ArrayableInterface|Stringable|array|string|int|null $needles, ?string $column = null, bool $strict = false): static
    {
        $this->source = Arrays::keepValues($this->source, $needles, $column, $strict);
        return $this;
    }


    /**
     * Remove source values on the specified needles with the specified match mode
     *
     * @param ArrayableInterface|Stringable|array|string|int|null $needles
     * @param string|null                              $column
     * @param bool                                     $strict
     *
     * @return static
     */
    public function removeValues(ArrayableInterface|Stringable|array|string|int|null $needles, ?string $column = null, bool $strict = false): static
    {
        $this->source = Arrays::removeValues($this->source, $needles, $column, $strict);
        return $this;
    }


    /**
     * Returns the first key contained in this object without changing the internal pointer
     *
     * @return Stringable|string|float|int|null
     */
    public function getFirstKey(): Stringable|string|float|int|null
    {
        if (empty($this->source)) {
            return null;
        }

        return array_key_first($this->source);
    }


    /**
     * Returns the last key contained in this object without changing the internal pointer
     *
     * @return Stringable|string|float|int|null
     */
    public function getLastKey(): Stringable|string|float|int|null
    {
        if (empty($this->source)) {
            return null;
        }

        return array_key_last($this->source);
    }


    /**
     * Returns the first element contained in this object without changing the internal pointer
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function getFirstValue(): mixed
    {
        if (empty($this->source)) {
            return null;
        }

        return $this->ensureObject(array_key_first($this->source));
    }


    /**
     * Returns the last element contained in this object without changing the internal pointer
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function getLastValue(): mixed
    {
        if (empty($this->source)) {
            return null;
        }

        return $this->ensureObject(array_key_last($this->source));
    }


    /**
     * Returns if the specified key exists or not
     *
     * @param Stringable|string|int $key
     *
     * @return bool
     */
    public function keyExists(Stringable|string|int $key): bool
    {
        if (is_object($key)) {
            $key = (string) $key;
        }

        return array_key_exists($key, $this->source);
    }


    /**
     * Returns if the specified value exists in this Iterator or not
     *
     * @note Wrapper for IteratorCore::exists()
     *
     * @param mixed $value
     * @param bool  $strict
     *
     * @return bool
     */
    public function valueExists(mixed $value, bool $strict = true): bool
    {
        return in_array($value, $this->source, $strict);
    }
}
