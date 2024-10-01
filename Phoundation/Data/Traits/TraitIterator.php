<?php

/**
 * Trait Iterator
 *
 * This is a very basic implementation of the default PHP iterator class.
 *
 * This trait contains the basic Iterator methods plus the following methods:
 *
 * IteratorBase::__toString(): string
 * IteratorBase::__toArray(): array
 * IteratorBase::getSource(): array
 * IteratorBase::getSourceKeys(): array
 * IteratorBase::setSource(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null): static
 * IteratorBase::getCount(): int
 * IteratorBase::count(): int
 * IteratorBase::clear(): static
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Json;
use ReturnTypeWillChange;


trait TraitIterator
{
    use TraitDataSourceArray;


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
     * Returns the current entry
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function current(): mixed
    {
        return $this->source[key($this->source)];
    }


    /**
     * Progresses the internal pointer to the next entry
     *
     * @return void
     */
    public function next(): void
    {
        next($this->source);
    }


    /**
     * Progresses the internal pointer to the previous entry
     *
     * @return void
     */
    public function previous(): void
    {
        prev($this->source);
    }


    /**
     * Returns the current key for the current button
     *
     * @return string|int|null
     */
    public function key(): string|int|null
    {
        return key($this->source);
    }


    /**
     * Returns if the current pointer is valid or not
     *
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
            // We can't check if IteratorCore::source[null] exists because IteratorCore::source[""] will also respond to that,
            // same goes for isset() and array_key_exists()
            // current() will also return NULL when out of range, so assume that IteratorCore::source[null]
            // has a non-NULL value. If we're really in range, current() will give a non-NULL value, like
            // IteratorCore::source[null], and we'll know we're in range. If they are not equal (current() gives NULL) then
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
     * @return void
     */
    public function rewind(): void
    {
        reset($this->source);
    }
}
