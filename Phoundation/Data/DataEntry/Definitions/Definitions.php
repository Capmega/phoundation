<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Definitions;

use PDOStatement;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\DataPrefix;
use Phoundation\Data\Traits\DataTable;
use Phoundation\Exception\OutOfBoundsException;
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
    use DataTable;
    use DataPrefix {
        getPrefix as getFieldPrefix;
        setPrefix as setFieldPrefix;
    }


    /**
     * Iterator class constructor
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null $execute
     */
    public function __construct(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null)
    {
        if ($source or $execute) {
            throw new OutOfBoundsException(tr('Definitions class constructor should not receive any parameters'));
        }
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

        $this->source[$field->getField()] = $field;
        return $this;
    }


    /**
     * Returns the current Definition object
     *
     * @return DefinitionInterface
     */
    public function current(): DefinitionInterface
    {
        return current($this->source);
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
        return $this->source[$key];
    }


    /**
     * Returns the first Definition entry
     *
     * @return DefinitionInterface
     */
    public function getFirst(): DefinitionInterface
    {
        return array_first($this->source);
    }


    /**
     * Returns the last Definition entry
     *
     * @return DefinitionInterface
     */
    public function getLast(): DefinitionInterface
    {
        return array_last($this->source);
    }
}