<?php

namespace Phoundation\Data;

use Composer\XdebugHandler\Process;
use Iterator;
use Phoundation\Cli\Cli;
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
     * A list of filters that will filter the list data when being loaded
     *
     * @var array $filters
     */
    protected array $filters = [];

    /**
     * The iterator position
     *
     * @var int $position
     */
    protected int $position = 0;

    /**
     * The class for the items in this list when the item is dynamically created
     *
     * @var string
     */
    protected string $entry_class;



    /**
     * DataList class constructor
     *
     * @param DataEntry|null $parent
     */
    public function __construct(?DataEntry $parent = null)
    {
        // Validate the entry class
        if (isset($this->entry_class)) {
            if (!is_subclass_of($this->entry_class, DataEntry::class)) {
                throw new OutOfBoundsException(tr('Specified entry_class is invalid. The class should be a sub class of DataEntry::class but is a ":class"', [
                    ':class' => $this->entry_class
                ]));
            }
        } else {
            throw new OutOfBoundsException(tr('DataList class has not yet been set. The class should contain some DataEntry::class compatible class name'));
        }

        $this->parent = $parent;

        if ($parent) {
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
     * Returns if all (or optionally any) of the specified entries are in this list
     *
     * @param DataList|array|string $list
     * @param bool $all
     * @return bool
     */
    public function contains(DataList|array|string $list, bool $all = true): bool
    {
        if (is_string($list)) {
            $list = explode(',', $list);
        }

        foreach ($list as $entry) {
            if (!in_array($entry, $this->list)) {
                if ($all) {
                    // Ann need to be in the array but we found one missing
                    return false;
                }
            } else {
                if (!$all) {
                    // only one needs to be in the array, we found one, we're good!
                    return true;
                }
            }
        }

        // All were in the array
        return true;
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
     * Returns all configured filters to apply when loading the data list
     *
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }



    /**
     * Set multiple filters to apply when loading the data list
     *
     * @note This will clear all already defined filters
     * @param array $filters
     * @return $this
     */
    public function setFilters(array $filters): static
    {
        $this->filters = [];
        return $this->addFilters($filters);
    }



    /**
     * Add multiple filters to apply when loading the data list
     *
     * @param array $filters
     * @return $this
     */
    public function addFilters(array $filters): static
    {
        foreach ($filters as $key => $value) {
            $this->addFilter($key, $value);
        }

        return $this;
    }



    /**
     * Add a filter to apply when loading the data list
     *
     * @param string $key
     * @param array|string|int|null $value
     * @return $this
     */
    public function addFilter(string $key, array|string|int|null $value): static
    {
        if ($value !== null) {
            if ($key === 'status') {
                $this->filters[$key] = get_null($value);
            } else {
                $this->filters[$key] = $value;
            }
        }

        return $this;
    }



    /**
     * Add a filter to apply when loading the data list
     *
     * @param string $key
     * @return $this
     */
    public function removeFilter(string $key): static
    {
        unset($this->filters[$key]);
        return $this;
    }



    /**
     * Remove all filters to apply when loading the data list
     *
     * @return $this
     */
    public function clearFilters(): static
    {
        $this->filters = [];
        return $this;
    }



    /**
     * Returns the item with the specified identifier
     *
     * @param int $identifier
     * @return DataEntry|null
     */
    #[ReturnTypeWillChange] public function get(int $identifier): ?DataEntry
    {
        $entry = isset_get($this->list[$identifier]);

        if ($entry and !is_object($entry)) {
            $entry = new $this->entry_class($entry);
        }

        return $entry;
    }



    /**
     * Returns the current item
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function current(): DataEntry
    {
        $entry = $this->list[$this->position];

        if ($entry and !is_object($entry)) {
            $entry = new $this->entry_class($entry);
        }

        return $entry;
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
     * Creates and returns an HTML table for the data in this list
     *
     * @param string $class
     * @return Table
     */
    public function htmlTable(string $class = Table::class): Table
    {
        if (!is_subclass_of($class, Table::class)) {
            throw new OutOfBoundsException(tr('Invalid class ":class" specified, the class must be a subclass of Table::class', [
                ':class' => $class
            ]));
        }

        $this->ensureLoaded();

        // Create and return the table
        return $class::new()
            ->setSourceData($this->list);
    }



    /**
     * Creates and returns a CLI table for the data in this list
     *
     * @param array|null $columns
     * @param string|null $id_column
     * @return void
     */
    public function CliDisplayTable(?array $columns = null, ?string $id_column = 'id'): void
    {
        $this->ensureLoaded();
        Cli::displayTable($this->list, $columns, $id_column);
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
     * @param DataEntry|int|null $entry
     * @return $this
     */
    protected function removeEntry(DataEntry|int|null $entry): static
    {
        if ($entry) {
            if (is_object($entry)) {
                $entry = $entry->getId();
            }

            unset($this->list[$entry]);
        }

        return $this;
    }



    /**
     * Remove all the entries from the DataList
     *
     * @return $this
     */
    protected function clearEntries(): static
    {
        $this->list = [];
        return $this;
    }



    /**
     * If the list has not yet loaded its content, do so now
     *
     * @return void
     */
    protected function ensureLoaded(): void
    {
        if (!isset($this->list)) {
            $this->load();
        }
    }



    /**
     * Load the data list elements from database
     *
     * @param string|null $columns
     * @return static
     */
    abstract protected function load(?string $columns = null): static;



    /**
     * Save the data list elements to database
     *
     * @return static
     */
    abstract public function save(): static;
}