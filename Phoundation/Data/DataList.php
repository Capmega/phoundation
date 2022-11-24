<?php

namespace Phoundation\Data;

use Iterator;
use Phoundation\Exception\OutOfBoundsException;


/**
 * DataList trait
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Data
 */
abstract class DataList implements Iterator
{
    /**
     * The data list
     *
     * @var array $list
     */
    protected array $list;

    /**
     * The iterator position
     *
     * @var int $position
     */
    protected int $position = 0;



    /**
     * Returns new Roles object
     *
     * @return static
     */
    public static function new(): static
    {
        return new static();
    }



    /**
     * Add the specified data entry to the data list
     *
     * @param DataEntry $entry
     * @return $this
     */
    public function add(DataEntry $entry): static
    {
        $this->list[$entry->getId()] = $entry;
        return $this;
    }



    /**
     * Remove the specified data entry from the data list
     *
     * @param DataEntry $entry
     * @return $this
     */
    public function remove(DataEntry $entry): static
    {
        unset($this->list[$entry->getId()]);
        return $this;
    }



    /**
     * Returns the current item
     *
     * @return mixed
     */
    public function current(): DataEntry
    {
        return $this->list[$this->position];
    }



    /**
     * Jumps to the next element
     *
     * @return static
     */
    public function next(): static
    {
        ++$this->position;
        return $this;
    }



    /**
     * Jumps to the next element
     *
     * @return static
     */
    public function previous(): static
    {
        if ($this->position > 0) {
            throw new OutOfBoundsException(tr('Cannot jump to previous element, the position is already at 0'));
        }

        --$this->position;
        return $this;
    }



    /**
     * Returns the current iteraor position
     *
     * @return int
     */
    public function key(): int
    {
        return $this->position;
    }



    /**
     * Returns if the current element exists or not
     *
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->array[$this->position]);
    }



    /**
     *
     *
     * @return void
     */
    public function rewind(): void
    {
        $this->position = 0;
    }



    /**
     * Load the data list elements from database
     *
     * @return static
     */
    abstract protected function load(): static;



    /**
     * Save the data list elements to database
     *
     * @return static
     */
    abstract public function save(): static;
}