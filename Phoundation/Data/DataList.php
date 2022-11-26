<?php

namespace Phoundation\Data;

use Iterator;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Elements\Table;
use ReturnTypeWillChange;



/**
 * Class DataList
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
     * The list parent
     *
     * @var DataEntry|null $parent
     */
    protected ?DataEntry $parent;

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
     * DataList class constructor
     *
     * @param DataEntry|null $parent
     * @param bool $load
     */
    public function __construct(?DataEntry $parent = null, bool $load = false)
    {
        $this->parent = $parent;

        if ($parent and $load) {
            $this->load();
        }
    }



    /**
     * Returns new Roles object
     *
     * @param DataEntry|null $parent
     * @return static
     */
    public static function new(?DataEntry $parent = null): static
    {
        return new static($parent);
    }



    /**
     * Returns if the specified data entry exists in the data list
     *
     * @param DataEntry|int $entry
     * @return bool
     */
    public function exists(DataEntry|int $entry): bool
    {
        if (is_integer($entry)) {
            return array_key_exists($entry, $this->list);
        }

        return array_key_exists($entry->getId(), $this->list);
    }



    /**
     * Returns the entire internal list
     *
     * @return array
     */
    public function list(): array
    {
        return $this->list;
    }



    /**
     * Returns the item with the specified identifier
     *
     * @param int $identifier
     * @return DataEntry|null
     */
    #[ReturnTypeWillChange] public function get(int $identifier): ?DataEntry
    {
        return isset_get($this->list[$identifier]);
    }



    /**
     * Returns the current item
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function current(): DataEntry
    {
        return $this->list[$this->position];
    }



    /**
     * Jumps to the next element
     *
     * @return static
     */
    #[ReturnTypeWillChange] public function next(): static
    {
        ++$this->position;
        return $this;
    }



    /**
     * Jumps to the next element
     *
     * @return static
     */
    #[ReturnTypeWillChange] public function previous(): static
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
     * Rewinds the internal pointer to 0
     *
     * @return static
     */
    #[ReturnTypeWillChange] public function rewind(): static
    {
        $this->position = 0;
        return $this;
    }



    /**
     * Creates and returns a table for the data in this list
     *
     * @param string $class
     * @return Table
     */
    public function table(string $class = Table::class): Table
    {
        if (!is_subclass_of($class, Table::class)) {
            throw new OutOfBoundsException(tr('Invalid class ":class" specified, the class must be a subclass of Table::class', [
                ':class' => $class
            ]));
        }

        // Create and return the table
        return $class::new()
            ->setSourceData($this->list);
    }



    /**
     * Add the specified data entry to the data list
     *
     * @param DataEntry|null $entry
     * @return $this
     */
    protected function addEntry(?DataEntry $entry): static
    {
        if ($entry) {
            $this->list[$entry->getId()] = $entry;
        }

        return $this;
    }



    /**
     * Remove the specified data entry from the data list
     *
     * @param DataEntry $entry
     * @return $this
     */
    protected function removeEntry(DataEntry $entry): static
    {
        unset($this->list[$entry->getId()]);
        return $this;
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