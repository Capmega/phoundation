<?php

/**
 * Class Definitions
 *
 * Contains a collection of Definition objects for a DataEntry class and can validate the values
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Definitions;

use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\IteratorCore;
use Phoundation\Data\Traits\TraitDataDataEntry;
use Phoundation\Data\Traits\TraitDataPrefix;
use Phoundation\Data\Traits\TraitDataTable;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Traits\TraitButtons;
use Stringable;


class Definitions extends IteratorCore implements DefinitionsInterface
{
    use TraitButtons;
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
     * Definitions class constructor
     *
     * @param DataEntryInterface|null $data_entry
     */
    public function __construct(?DataEntryInterface $data_entry = null)
    {
        $this->data_entry = $data_entry;
    }


    /**
     * Returns a new Definitions object
     *
     * @param DataEntryInterface|null $data_entry
     *
     * @return static
     */
    public static function new(?DataEntryInterface $data_entry = null): static
    {
        return new static($data_entry);
    }


    /**
     * Ensures that the value is a DefinitionInterface object and that the prefix is automatically added to the column
     * name
     *
     * @param mixed $value
     *
     * @return void
     */
    protected function ensureValueAndPrefix(mixed $value): void
    {
        if (!($value instanceof DefinitionInterface)) {
            throw new OutOfBoundsException(tr('Cannot add variable ":value" to the DataEntry definitions list, it is not a DefinitionInterface object', [
                ':value' => $value,
            ]));
        }

        if ($this->prefix) {
            $value->setColumn($this->prefix . $value->getColumn());
        }
    }


    /**
     * Adds the specified Definition object to the "definitions" list
     *
     * @param mixed                            $value
     * @param float|Stringable|int|string|null $key
     * @param bool                             $skip_null_values
     * @param bool                             $exception
     *
     * @return static
     */
    public function append(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null_values = true, bool $exception = true): static
    {
        $this->ensureValueAndPrefix($value);

        return parent::append($value, $key ?? $value->getColumn(), $skip_null_values, $exception);
    }


    /**
     * Add the specified definition to this definitions class
     *
     * @note if no key was specified, the entry will be assigned as-if a new array entry
     *
     * @param mixed                            $value
     * @param Stringable|string|float|int|null $key
     * @param bool                             $skip_null_values
     * @param bool                             $exception
     *
     * @return static
     */
    public function prepend(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null_values = true, bool $exception = true): static
    {
        $this->ensureValueAndPrefix($value);

        return parent::prepend($value, $key ?? $value->getColumn(), $skip_null_values, $exception);
    }


    /**
     * Add the specified value to the iterator array using an optional key BEFORE the specified $before_key
     *
     * @note if no key was specified, the entry will be assigned as-if a new array entry
     *
     * @param mixed                            $value
     * @param Stringable|string|float|int|null $key
     * @param Stringable|string|float|int|null $before
     * @param bool                             $skip_null_values
     * @param bool                             $exception
     *
     * @return static
     */
    public function prependBeforeKey(mixed $value, Stringable|string|float|int|null $key = null, Stringable|string|float|int|null $before = null, bool $skip_null_values = true, bool $exception = true): static
    {
        $this->ensureValueAndPrefix($value);

        return parent::prependBeforeKey($value, $key ?? $value->getColumn(), $before, $skip_null_values, $exception);
    }


    /**
     * Add the specified value to the iterator array using an optional key BEFORE the specified $before_value
     *
     * @note if no key was specified, the entry will be assigned as-if a new array entry
     *
     * @param mixed                            $value
     * @param Stringable|string|float|int|null $key
     * @param mixed                            $before
     * @param bool                             $strict
     * @param bool                             $skip_null_values
     * @param bool                             $exception
     *
     * @return static
     */
    public function prependBeforeValue(mixed $value, Stringable|string|float|int|null $key = null, mixed $before = null, bool $strict = false, bool $skip_null_values = true, bool $exception = true): static
    {
        $this->ensureValueAndPrefix($value);

        return parent::prependBeforeValue($value, $key ?? $value->getColumn(), $before, $strict, $skip_null_values, $exception);
    }


    /**
     * Add the specified value to the iterator array using an optional key AFTER the specified $after_key
     *
     * @note if no key was specified, the entry will be assigned as-if a new array entry
     *
     * @param mixed                            $value
     * @param Stringable|string|float|int|null $key
     * @param Stringable|string|float|int|null $after
     * @param bool                             $skip_null_values
     * @param bool                             $exception
     *
     * @return static
     */
    public function appendAfterKey(mixed $value, Stringable|string|float|int|null $key = null, Stringable|string|float|int|null $after = null, bool $skip_null_values = true, bool $exception = true): static
    {
        $this->ensureValueAndPrefix($value);

        return parent::appendAfterKey($value, $key ?? $value->getColumn(), $after, $skip_null_values, $exception);
    }


    /**
     * Add the specified value to the iterator array using an optional key AFTER the specified $after_value
     *
     * @note if no key was specified, the entry will be assigned as-if a new array entry
     *
     * @param mixed                            $value
     * @param Stringable|string|float|int|null $key
     * @param mixed                            $after
     * @param bool                             $strict
     * @param bool                             $skip_null_values
     * @param bool                             $exception
     *
     * @return static
     */
    public function appendAfterValue(mixed $value, Stringable|string|float|int|null $key = null, mixed $after = null, bool $strict = false, bool $skip_null_values = true, bool $exception = true): static
    {
        $this->ensureValueAndPrefix($value);

        return parent::appendAfterValue($value, $key ?? $value->getColumn(), $after, $strict, $skip_null_values, $exception);
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
     * @param Stringable|string|float|int $target
     * @param bool                        $exception
     *
     * @return DefinitionInterface
     */
    public function renameKey(Stringable|string|float|int $key, Stringable|string|float|int $target, bool $exception = true): DefinitionInterface
    {
        // Rename Definition in Iterator and Definition object itself
        return parent::renameKey($key, $target, $exception)
                     ->setColumn($target);
    }


    /**
     * Returns the specified column
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $exception
     *
     * @return DefinitionInterface|null
     */
    public function get(Stringable|string|float|int $key, bool $exception = true): ?DefinitionInterface
    {
        return parent::get($key, $exception);
    }


    /**
     * Direct method to hide entries
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $exception
     *
     * @return static
     */
    public function hide(Stringable|string|float|int $key, bool $exception = true): static
    {
        $this->get($key, $exception)
            ->setHidden(true);

        return $this;
    }


    /**
     * Direct method to show entries
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $exception
     *
     * @return static
     */
    public function show(Stringable|string|float|int $key, bool $exception = true): static
    {
        $this->get($key, $exception)
            ->setHidden(false);

        return $this;
    }


    /**
     * Direct method to render or not render entries
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $render
     * @param bool                        $exception
     *
     * @return static
     */
    public function setRender(Stringable|string|float|int $key, bool $render, bool $exception = true): static
    {
        $this->get($key, $exception)
            ->setRender($render);

        return $this;
    }


    /**
     * Direct method to set size for entries
     *
     * @param Stringable|string|float|int $key
     * @param int                         $size
     * @param bool                        $exception
     *
     * @return static
     */
    public function setSize(Stringable|string|float|int $key, int $size, bool $exception = true): static
    {
        $this->get($key, $exception)
             ->setSize($size);

        return $this;
    }


    /**
     * Direct method to make entries readonly
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $render
     * @param bool                        $exception
     *
     * @return static
     */
    public function setReadonly(Stringable|string|float|int $key, bool $render, bool $exception = true): static
    {
        $this->get($key, $exception)
            ->setReadonly($render);

        return $this;
    }


    /**
     * Direct method to make entries disabled
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $render
     * @param bool                        $exception
     *
     * @return static
     */
    public function setDisabled(Stringable|string|float|int $key, bool $render, bool $exception = true): static
    {
        $this->get($key, $exception)
            ->setDisabled($render);

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
     *
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
