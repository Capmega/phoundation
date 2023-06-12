<?php

namespace Phoundation\Data\DataEntry;

use Phoundation\Data\Classes\Iterator;
use Phoundation\Data\DataEntry\Interfaces\DataEntryFieldDefinition;
use Phoundation\Data\Traits\UsesNewTable;


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
class DataEntryFieldDefinitions extends Iterator implements Interfaces\DataEntryFieldDefinitionsInterface
{
    use UsesNewTable;


    /**
     * Adds the specified DataEntryFieldDefinition to the fields list
     *
     * @param DataEntryFieldDefinition $field
     * @return static
     */
    public function add(Interfaces\DataEntryFieldDefinition $field): static
    {
        $this->list[$field->getField()] = $field;
        return $this;
    }


    /**
     * Returns the current DataEntryFieldDefinition object
     *
     * @return Interfaces\DataEntryFieldDefinition
     */
    public function current(): Interfaces\DataEntryFieldDefinition
    {
        return current($this->list);
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
        return $this->list[$key];
    }


    /**
     * Returns the first DataEntryFieldDefinition entry
     *
     * @return Interfaces\DataEntryFieldDefinition
     */
    public function getFirst(): Interfaces\DataEntryFieldDefinition
    {
        return array_first($this->list);
    }


    /**
     * Returns the last DataEntryFieldDefinition entry
     *
     * @return Interfaces\DataEntryFieldDefinition
     */
    public function getLast(): Interfaces\DataEntryFieldDefinition
    {
        return array_last($this->list);
    }
}