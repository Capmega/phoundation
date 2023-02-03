<?php

namespace Phoundation\Core\Classes;

use Phoundation\Exception\NotExistsException;
use Phoundation\Utils\Json;
use ReturnTypeWillChange;



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
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class Iterator implements \Phoundation\Core\Interfaces\Iterator
{
    protected array $list = [];



    /**
     * Returns the contents of this iterator object as a JSON string
     *
     * @return string
     */
    public function __toString(): string
    {
        return Json::encode($this->list);
    }



    /**
     * Returns the contents of this iterator object as an array
     *
     * @return array
     */
    public function __toArray(): array
    {
die('aaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
        return $this->list;
    }



    /**
     * Returns a new Iterator object
     *
     * @return static
     */
    public static function new(): static
    {
        return new static();
    }



    /**
     * Returns the current button
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function current(): mixed
    {
        return current($this->list);
    }



    /**
     * Progresses the internal pointer to the next button
     *
     * @return static
     */
    #[ReturnTypeWillChange] public function next(): static
    {
        next($this->list);
        return $this;
    }



    /**
     * Returns the current key for the current button
     *
     * @return string
     */
    #[ReturnTypeWillChange] public function key(): string
    {
        return key($this->list);
    }



    /**
     * Returns if the current pointer is valid or not
     *
     * @todo Is this really really required? Since we're using internal array pointers anyway, it always SHOULD be valid
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->list[key($this->list)]);
    }



    /**
     * Rewinds the internal pointer
     *
     * @return static
     */
    #[ReturnTypeWillChange] public function rewind(): static
    {
        reset($this->list);
        return $this;

    }


    /**
     * Returns a list of all internal values with their keys
     *
     * @return mixed
     */
    public function getList(): array
    {
        return $this->list;
    }



    /**
     * Returns value for the specified key
     *
     * @param string|float|int $key
     * @param bool $exception
     * @return mixed
     */
    public function get(string|float|int $key, bool $exception = false): mixed
    {
        if ($exception) {
            if (!array_key_exists($key, $this->list)) {
                throw new NotExistsException(tr('The key ":key" does not exist in this object', [':key' => $key]));
            }
        }

        return isset_get($this->list[$key]);
    }



    /**
     * Returns the amount of items contained in this object
     *
     * @return int
     */
    public function getCount(): int
    {
        return count($this->list);
    }



    /**
     * Returns the first element contained in this object without changing the internal pointer
     *
     * @return mixed
     */
    public function getFirst(): mixed
    {
        return $this->list[array_key_first($this->list)];
    }



    /**
     * Returns the last element contained in this object without changing the internal pointer
     *
     * @return mixed
     */
    public function getLast(): mixed
    {
        return $this->list[array_key_last($this->list)];
    }



    /**
     * Clears all the internal content for this object
     *
     * @return mixed
     */
    public function clear(): static
    {
        $this->list = [];
        return $this;
    }



    /**
     * Deletes the specified key
     *
     * @param float|int|string $key
     * @return static
     */
    public function delete(float|int|string $key): static
    {
        unset($this->list[$key]);
        return $this;
    }
}