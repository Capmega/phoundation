<?php

namespace Phoundation\Data\DataEntry;

use Phoundation\Data\Traits\UsesNewTable;
use Phoundation\Utils\Json;


/**
 * Class DataEntryFieldDefinitions
 *
 * Contains a collection of DataEntryFieldDefinition objects for a DataEntry class and can validate the values
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
class DataEntryFieldDefinitions implements Interfaces\DataEntryFieldDefinitionsInterface
{
    use UsesNewTable;

    /**
     * The data entry fields for the DataEntry object
     *
     * @var array $fields
     */
    protected array $fields = [];


    /**
     * Adds the specified DataEntryFieldDefinition to the fields list
     *
     * @param Interfaces\DataEntryFieldDefinition $field
     * @return static
     */
    public function add(Interfaces\DataEntryFieldDefinition $field): static
    {
        $this->fields[$field->getField()] = $field;
        return $this;
    }


    /**
     * Prepends the specified DataEntryFieldDefinition to the fields list
     *
     * @param Interfaces\DataEntryFieldDefinition $field
     * @return static
     */
    public function prepend(Interfaces\DataEntryFieldDefinition $field): static
    {
        array_unshift($this->fields, $field);
        return $this;
    }


    /**
     * Returns the current DataEntryFieldDefinition object
     *
     * @return Interfaces\DataEntryFieldDefinition
     */
    public function current(): Interfaces\DataEntryFieldDefinition
    {
        return current($this->fields);
    }


    /**
     * Progresses the internal pointer to the next DataEntryFieldDefinition object
     *
     * @return void
     */
    public function next(): void
    {
        next($this->fields);
    }


    /**
     * Returns the current key for the current menu
     *
     * @return float|int|string
     */
    public function key(): float|int|string
    {
        return key($this->fields);
    }


    /**
     * Returns if the current pointer is valid or not
     *
     * @todo Is this really really required? Since we're using internal array pointers anyway, it always SHOULD be valid
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->fields[key($this->fields)]);
    }


    /**
     * Rewinds the internal pointer
     *
     * @return void
     */
    public function rewind(): void
    {
        reset($this->fields);
    }


    /**
     * Returns the DataEntryFieldDefinitions fields array in a JSON string
     *
     * @return string
     */
    public function __toString(): string
    {
        return Json::encode($this->fields);
    }


    /**
     * Returns the DataEntryFieldDefinitions array
     *
     * @return array
     */
    public function __toArray(): array
    {
        return $this->fields;
    }


    /**
     * Returns the specified field
     *
     * @param float|int|string $key
     * @param bool $exception
     * @return Interfaces\DataEntryFieldDefinition
     */
    public function get(float|int|string $key, bool $exception = false): Interfaces\DataEntryFieldDefinition
    {
        return $this->fields[$key];
    }


    /**
     * Returns if the specified DataEntryFieldDefinition exists or not
     *
     * @param float|int|string $key
     * @return bool
     */
    public function exists(float|int|string $key): bool
    {
        return array_key_exists($key, $this->fields);
    }


    /**
     * Returns the DataEntryFieldDefinitions array
     *
     * @return array
     */
    public function getList(): array
    {
        return $this->fields;
    }


    /**
     * Returns the amount of DataEntryFieldDefinitions
     *
     * @return int
     */
    public function getCount(): int
    {
        return count($this->fields);
    }


    /**
     * Returns true if the list is empty and has no DataEntryFieldDefinition objects
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return !count($this->fields);
    }


    /**
     * Returns the first DataEntryFieldDefinition entry
     *
     * @return Interfaces\DataEntryFieldDefinition
     */
    public function getFirst(): Interfaces\DataEntryFieldDefinition
    {
        return array_first($this->fields);
    }


    /**
     * Returns the last DataEntryFieldDefinition entry
     *
     * @return Interfaces\DataEntryFieldDefinition
     */
    public function getLast(): Interfaces\DataEntryFieldDefinition
    {
        return array_last($this->fields);
    }


    /**
     * Clears the DataEntryFieldDefinitions list
     *
     * @return $this
     */
    public function clear(): static
    {
        $this->fields = [];
        return $this;
    }


    /**
     * Deletes the DataEntryFieldDefinitions with the specified key
     *
     * @return $this
     */
    public function delete(float|int|string $key): static
    {
        unset($this->fields[$key]);
        return $this;
    }
}