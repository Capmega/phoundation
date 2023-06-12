<?php

namespace Phoundation\Data\DataEntry\Interfaces;

use Phoundation\Data\Interfaces\Iterator;


/**
 * Class DataEntryFieldDefinitions
 *
 * Contains a collection of DataEntryFieldDefinition objects for a DataEntry class and can validate the values
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
interface DataEntryFieldDefinitionsInterface extends Iterator
{
    /**
     * UsesNewTable class constructor
     *
     * @param string|null $table
     */
    public function __construct(?string $table = null);

    /**
     * Returns a new static object
     *
     * @param string|null $table
     * @return static
     */
    public static function new(?string $table = null): static;

    /**
     * Returns the table
     *
     * @return string|null
     */
    public function getTable(): ?string;

    /**
     * Sets the table
     *
     * @param string|null $table
     * @return static
     */
    public function setTable(?string $table): static;

    /**
     * Adds the specified DataEntryFieldDefinition to the fields list
     *
     * @param DataEntryFieldDefinition $field
     * @return static
     */
    public function add(DataEntryFieldDefinition $field): static;

    /**
     * Returns the current DataEntryFieldDefinition object
     *
     * @return DataEntryFieldDefinition
     */
    public function current(): DataEntryFieldDefinition;

    /**
     * Progresses the internal pointer to the next DataEntryFieldDefinition object
     *
     * @return static
     */
    public function next(): static;

    /**
     * Returns the current key for the current menu
     *
     * @return float|int|string
     */
    public function key(): float|int|string;

    /**
     * Returns if the current pointer is valid or not
     *
     * @todo Is this really really required? Since we're using internal array pointers anyway, it always SHOULD be valid
     * @return bool
     */
    public function valid(): bool;

    /**
     * Rewinds the internal pointer
     *
     * @return static
     */
    public function rewind(): static;

    /**
     * Returns the DataEntryFieldDefinitions fields array in a JSON string
     *
     * @return string
     */
    public function __toString(): string;

    /**
     * Returns the DataEntryFieldDefinitions array
     *
     * @return array
     */
    public function __toArray(): array;

    /**
     * Returns the specified field
     *
     * @param float|int|string $key
     * @param bool $exception
     * @return DataEntryFieldDefinition
     */
    public function get(float|int|string $key, bool $exception = false): DataEntryFieldDefinition;

    /**
     * Returns if the specified DataEntryFieldDefinition exists or not
     *
     * @param float|int|string $key
     * @return bool
     */
    public function exists(float|int|string $key): bool;

    /**
     * Returns the DataEntryFieldDefinitions array
     *
     * @return array
     */
    public function getList(): array;

    /**
     * Returns the amount of DataEntryFieldDefinitions
     *
     * @return int
     */
    public function getCount(): int;

    /**
     * Returns true if the list is empty and has no DataEntryFieldDefinition objects
     *
     * @return bool
     */
    public function isEmpty(): bool;

    /**
     * Returns the first DataEntryFieldDefinition entry
     *
     * @return DataEntryFieldDefinition
     */
    public function getFirst(): DataEntryFieldDefinition;

    /**
     * Returns the last DataEntryFieldDefinition entry
     *
     * @return DataEntryFieldDefinition
     */
    public function getLast(): DataEntryFieldDefinition;

    /**
     * Clears the DataEntryFieldDefinitions list
     *
     * @return $this
     */
    public function clear(): static;

    /**
     * Deletes the DataEntryFieldDefinitions with the specified key
     *
     * @return $this
     */
    public function delete(float|int|string $key): static;
}