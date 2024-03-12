<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Definitions;

use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\TraitDataDataEntry;
use Phoundation\Data\Traits\TraitDataPrefix;
use Phoundation\Data\Traits\TraitDataTable;
use Phoundation\Exception\OutOfBoundsException;
use Stringable;


/**
 * Class Definitions
 *
 * Contains a collection of Definition objects for a DataEntry class and can validate the values
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
class Definitions extends Iterator implements DefinitionsInterface
{
    use TraitDataDataEntry;
    use TraitDataTable;
    use TraitDataPrefix {
        getPrefix as getColumnPrefix;
        setPrefix as setColumnPrefix;
    }


    /**
     * Tracks if meta-information can be visible or not
     *
     * @var bool
     */
    protected bool $meta_visible = true;


    /**
     * Adds the specified Definition object to the definitions list
     *
     * @param mixed $value
     * @param float|Stringable|int|string|null $key
     * @param bool $skip_null
     * @param bool $exception
     * @return $this
     */
    public function add(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null = true, bool $exception = true): static
    {
        if (!($value instanceof DefinitionInterface)) {
            throw new OutOfBoundsException(tr('Cannot add variable ":value" to the DataEntry definitions list, it is not a DefinitionInterface object', [
                ':value' => $value
            ]));
        }

        if ($this->prefix) {
            $value->setColumn($this->prefix . $value->getColumn());
        }

        return parent::add($value, $key ?? $value->getColumn(), $skip_null, $exception);
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
     * Returns the specified column
     *
     * @param Stringable|string|float|int $key
     * @param bool $exception
     * @return DefinitionInterface
     */
    public function get(Stringable|string|float|int $key, bool $exception = true): DefinitionInterface
    {
        return parent::get($key, $exception);
    }


    /**
     * Returns the specified column
     *
     * @param Stringable|string|float|int $key
     * @param Stringable|string|float|int $target
     * @param bool $exception
     * @return DefinitionInterface
     */
    public function renameKey(Stringable|string|float|int $key, Stringable|string|float|int $target, bool $exception = true): DefinitionInterface
    {
        // Rename Definition in Iterator and Definition object itself
        return parent::renameKey($key, $target, $exception)->setColumn($target);
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
    public function getFirstValue(): DefinitionInterface
    {
        return $this->source[array_key_first($this->source)];
    }


    /**
     * Returns the last Definition entry
     *
     * @return DefinitionInterface
     */
    public function getLastValue(): DefinitionInterface
    {
        return $this->source[array_key_last($this->source)];
    }
}
