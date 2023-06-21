<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Definitions;

use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\DataPrefix;
use Phoundation\Data\Traits\UsesNewTable;
use Stringable;


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
class Definitions extends Iterator implements DefinitionsInterface
{
    use UsesNewTable;
    use DataPrefix {
        getPrefix as getFieldPrefix;
        setPrefix as setFieldPrefix;
    }


    /**
     * Adds the specified Definition to the fields list
     *
     * @param DefinitionInterface $field
     * @return static
     */
    public function add(DefinitionInterface $field): static
    {
        if ($this->prefix) {
            $field->setField($this->prefix . $field->getField());
        }

        $this->list[$field->getField()] = $field;
        return $this;
    }


    /**
     * Returns the current Definition object
     *
     * @return DefinitionInterface
     */
    public function current(): DefinitionInterface
    {
        return current($this->list);
    }


    /**
     * Returns the specified field
     *
     * @param Stringable|string|float|int $key
     * @param bool $exception
     * @return DefinitionInterface
     */
    public function get(Stringable|string|float|int $key, bool $exception = false): DefinitionInterface
    {
        return $this->list[$key];
    }


    /**
     * Returns the first Definition entry
     *
     * @return DefinitionInterface
     */
    public function getFirst(): DefinitionInterface
    {
        return array_first($this->list);
    }


    /**
     * Returns the last Definition entry
     *
     * @return DefinitionInterface
     */
    public function getLast(): DefinitionInterface
    {
        return array_last($this->list);
    }
}