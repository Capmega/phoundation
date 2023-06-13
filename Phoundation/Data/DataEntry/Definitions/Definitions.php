<?php

namespace Phoundation\Data\DataEntry\Definitions;

use Phoundation\Data\Classes\Iterator;
use Phoundation\Data\DataEntry\Interfaces;
use Phoundation\Data\DataEntry\Interfaces\DefinitionInterface;
use Phoundation\Data\Traits\UsesNewTable;


/**
 * Class Definitions
 *
 * Contains a collection of Definition objects for a DataEntry class and can validate the values
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
class Definitions extends Iterator implements Interfaces\DefinitionsInterface
{
    use UsesNewTable;


    /**
     * Adds the specified Definition to the fields list
     *
     * @param \Phoundation\Data\DataEntry\Definitions\Definition $field
     * @return static
     */
    public function add(Interfaces\DefinitionInterface $field): static
    {
        $this->list[$field->getField()] = $field;
        return $this;
    }


    /**
     * Returns the current Definition object
     *
     * @return Interfaces\DefinitionInterface
     */
    public function current(): Interfaces\DefinitionInterface
    {
        return current($this->list);
    }


    /**
     * Returns the specified field
     *
     * @param float|int|string $key
     * @param bool $exception
     * @return Interfaces\DefinitionInterface
     */
    public function get(float|int|string $key, bool $exception = false): Interfaces\DefinitionInterface
    {
        return $this->list[$key];
    }


    /**
     * Returns the first Definition entry
     *
     * @return Interfaces\DefinitionInterface
     */
    public function getFirst(): Interfaces\DefinitionInterface
    {
        return array_first($this->list);
    }


    /**
     * Returns the last Definition entry
     *
     * @return Interfaces\DefinitionInterface
     */
    public function getLast(): Interfaces\DefinitionInterface
    {
        return array_last($this->list);
    }
}