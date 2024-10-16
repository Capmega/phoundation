<?php

/**
 * Trait TraitDataSourceArray
 *
 * This trait contains the basic methods required to use a source array
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use PDOStatement;
use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Exception\NotExistsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Json;
use ReturnTypeWillChange;
use Stringable;


trait TraitDataSourceArray
{
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
        return Json::encode($this->source);
    }


    /**
     * Returns the source data when cast to array
     *
     * @return array
     */
    public function __toArray(): array
    {
        return $this->source;
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
     * Returns a list of all internal definition keys
     *
     * @return mixed
     */
    public function getSourceKeys(): array
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
     * Sets the source
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
     * @param bool                        $exception
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, bool $exception = true): mixed
    {
        // Does this entry exist?
        if (array_key_exists($key, $this->source)) {
            return $this->source[$key];
        }

        if ($exception) {
            // The key does not exist
            throw new NotExistsException(tr('The key ":key" does not exist in this ":class" object', [
                ':key'   => $key,
                ':class' => get_class($this),
            ]));
        }

        return null;
    }


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
    public function set(mixed $value, Stringable|string|float|int $key): static
    {
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
     * @param ArrayableInterface|array|string|int|null $needles
     * @param bool                                     $strict
     *
     * @return static
     */
    public function keepKeys(ArrayableInterface|array|string|int|null $needles, bool $strict = false): static
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
     * @param ArrayableInterface|array|string|int|null $needles
     * @param string|null                              $column
     * @param bool                                     $strict
     *
     * @return static
     */
    public function keepValues(ArrayableInterface|array|string|int|null $needles, ?string $column = null, bool $strict = false): static
    {
        $this->source = Arrays::keepValues($this->source, $needles, $column, $strict);
        return $this;
    }


    /**
     * Remove source values on the specified needles with the specified match mode
     *
     * @param ArrayableInterface|array|string|int|null $needles
     * @param string|null                              $column
     * @param bool                                     $strict
     *
     * @return static
     */
    public function removeValues(ArrayableInterface|array|string|int|null $needles, ?string $column = null, bool $strict = false): static
    {
        $this->source = Arrays::removeValues($this->source, $needles, $column, $strict);
        return $this;
    }
}
