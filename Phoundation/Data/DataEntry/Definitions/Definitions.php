<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Definitions;

use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\DataDataEntry;
use Phoundation\Data\Traits\DataPrefix;
use Phoundation\Data\Traits\DataTable;
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
    use DataDataEntry;
    use DataTable;
    use DataPrefix {
        getPrefix as getFieldPrefix;
        setPrefix as setFieldPrefix;
    }


    /**
     * Tracks if meta-information can be visible or not
     *
     * @var bool
     */
    protected bool $meta_visible = true;


    /**
     * Adds the specified Definition to the fields list
     *
     * @param DefinitionInterface $field
     * @return static
     */
    public function addDefinition(DefinitionInterface $field): static
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
    public function get(Stringable|string|float|int $key, bool $exception = true): DefinitionInterface
    {
        return $this->source[$key];
    }


    /**
     * Direct method to hide entries
     *
     * @param Stringable|string|float|int $key
     * @param bool $exception
     * @return static
     */
    public function hide(Stringable|string|float|int $key, bool $exception = true): static
    {
        $this->get($key, $exception)->setHidden(true);
        return $this;
    }


    /**
     * Direct method to show entries
     *
     * @param Stringable|string|float|int $key
     * @param bool $exception
     * @return static
     */
    public function show(Stringable|string|float|int $key, bool $exception = true): static
    {
        $this->get($key, $exception)->setHidden(false);
        return $this;
    }


    /**
     * Returns if meta-information is visible at all, or not
     *
     * @return bool
     */
    public function getMetaVisible(): bool
    {
        return $this->meta_visible;
    }


    /**
     * Sets if meta-information is visible at all, or not
     *
     * @param bool $meta_visible
     * @return static
     */
    public function setMetaVisible(bool $meta_visible): static
    {
        $this->meta_visible = $meta_visible;
        return $this;
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