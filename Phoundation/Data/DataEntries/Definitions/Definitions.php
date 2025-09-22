<?php

/**
 * Class Definitions
 *
 * Contains a collection of Definition objects for a DataEntry class and can validate the values
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Definitions;

use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Data\Exception\IteratorKeyExistsException;
use Phoundation\Data\IteratorCore;
use Phoundation\Data\Traits\TraitDataDataEntry;
use Phoundation\Data\Traits\TraitDataDisabled;
use Phoundation\Data\Traits\TraitDataPrefix;
use Phoundation\Data\Traits\TraitDataReadonly;
use Phoundation\Data\Traits\TraitDataTable;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Traits\TraitObjectButtons;
use ReturnTypeWillChange;
use Stringable;


class Definitions extends IteratorCore implements DefinitionsInterface
{
    use TraitObjectButtons;
    use TraitDataDataEntry;
    use TraitDataTable;
    use TraitDataReadonly;
    use TraitDataDisabled;
    use TraitDataPrefix {
        setPrefix as protected __setPrefix;
    }


    /**
     * Tracks if meta-information can be visible or not
     *
     * @var bool
     */
    protected bool $render_meta = true;


    /**
     * Definitions class constructor
     *
     * @param DataEntryInterface|null $data_entry
     */
    public function __construct(?DataEntryInterface $data_entry = null)
    {
        parent::__construct();
        $this->setDataEntryObject($data_entry);
    }


    /**
     * Returns the data types that are allowed and accepted for this data iterator
     *
     * @return string|null
     */
    public static function getDefaultContentDataType(): ?string
    {
        return DefinitionInterface::class;
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
     * Sets the column prefix for this Definition object
     *
     * @param string|null $prefix
     *
     * @return static
     */
    public function setPrefix(?string $prefix): static
    {
        // Apply the new prefix to all definitions
        foreach ($this->source as $value) {
            $value->setPrefix($prefix);
        }

        return $this->__setPrefix($prefix);
    }


    /**
     * Ensures that the value is a DefinitionInterface object and that the prefix is automatically added to the column
     * name
     *
     * @param mixed                            $value
     * @param Stringable|string|float|int|null $key
     *
     * @return string|null
     */
    protected function ensureValueAndPrefix(mixed $value, Stringable|string|float|int|null $key): ?string
    {
        if (!$value instanceof DefinitionInterface) {
            throw new OutOfBoundsException(tr('Cannot add the specified value ":value" to the Definitions list for DataEntry class ":class", it must be a DefinitionInterface object but is a ":type" instead', [
                ':class' => get_class_or_datatype($this->o_data_entry),
                ':value' => $value,
                ':type'  => get_class_or_datatype($value)
            ]));
        }

        $key = $key ?? $value->getColumn();

        if (in_array($key, ['connector'], true)) {
            throw new OutOfBoundsException(tr('The DataEntry ":class" class column / definition name ":column" is reserved and cannot be used for DataEntry columns', [
                ':class'  => ($this->o_data_entry ? $this->o_data_entry::class : '-'),
                ':column' => $key
            ]));
        }

        // Ensure the added Definition has DataEntry and prefix set
        $value->setDataEntryObject($this->o_data_entry)
              ->setReadonly($value->getReadonly() or $this->getReadonly())
              ->setDisabled($value->getDisabled() or $this->getDisabled())
              ->setPrefix($this->getPrefix());

        return $key;
    }


    /**
     * Adds the specified Definition object to the "definitions" list
     *
     * @param mixed                            $value
     * @param Stringable|string|float|int|null $key
     * @param bool                             $skip_null_values
     * @param bool                             $exception
     *
     * @return static
     *
     * @throws IteratorKeyExistsException
     */
    public function append(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null_values = true, bool $exception = true): static
    {
        try {
            $key = $this->ensureValueAndPrefix($value, $key);
            return parent::append($value, $key, $skip_null_values, $exception);

        } catch (IteratorKeyExistsException $e) {
            throw $e->addData(['keys' => $this->getSourceKeys()]);
        }
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
        try {
            $key = $this->ensureValueAndPrefix($value, $key);
            return parent::prepend($value, $key, $skip_null_values, $exception);

        } catch (IteratorKeyExistsException $e) {
            throw $e->addData(['keys' => $this->getSourceKeys()]);
        }
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
        try {
            $key = $this->ensureValueAndPrefix($value, $key);
            return parent::prependBeforeKey($value, $key, $before, $skip_null_values, $exception);

        } catch (IteratorKeyExistsException $e) {
            throw $e->addData(['keys' => $this->getSourceKeys()]);
        }
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
        try {
            $key = $this->ensureValueAndPrefix($value, $key);
            return parent::prependBeforeValue($value, $key, $before, $strict, $skip_null_values, $exception);

        } catch (IteratorKeyExistsException $e) {
            throw $e->addData(['keys' => $this->getSourceKeys()]);
        }
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
        try {
            $key = $this->ensureValueAndPrefix($value, $key);
            return parent::appendAfterKey($value, $key, $after, $skip_null_values, $exception);

        } catch (IteratorKeyExistsException $e) {
            throw $e->addData(['keys' => $this->getSourceKeys()]);
        }
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
        try {
            $key = $this->ensureValueAndPrefix($value, $key);
            return parent::appendAfterValue($value, $key, $after, $strict, $skip_null_values, $exception);

        } catch (IteratorKeyExistsException $e) {
            throw $e->addData(['keys' => $this->getSourceKeys()]);
        }
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
     * @param mixed                       $default
     * @param bool|null                   $exception
     *
     * @return DefinitionInterface|null
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, mixed $default = null, ?bool $exception = null): ?DefinitionInterface
    {
        return parent::get($key, $default, $exception);
    }


    /**
     * Direct method to hide entries
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $exception
     *
     * @return static
     */
    public function hideDefinition(Stringable|string|float|int $key, bool $exception = true): static
    {
        $this->get($key, exception: $exception)
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
    public function showDefinition(Stringable|string|float|int $key, bool $exception = true): static
    {
        $this->get($key, exception: $exception)
             ->setHidden(false);

        return $this;
    }


    /**
     * Modify the specified definition directly
     *
     * @param Stringable|string|float|int $key
     * @param array $key_values
     * @param bool $exception
     *
     * @return static
     */
    public function modifyDefinition(Stringable|string|float|int $key, array $key_values, bool $exception = true): static
    {
        $o_definition = $this->get($key, exception: $exception);

        foreach ($key_values as $key => $value) {
            $o_definition->set($value, $key);
        }

        return $this;
    }


    /**
     * Direct method to return weather the specified column renders or not
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $exception
     *
     * @return bool
     */
    public function isRendered(Stringable|string|float|int $key, bool $exception = true): bool
    {
        return (bool) $this->get($key, exception: $exception)?->getRender();
    }


    /**
     * Direct method to get if the specified key renders or not
     *
     * @param Stringable|string|float|int $key
     *
     * @return bool
     */
    public function getDefinitionRender(Stringable|string|float|int $key): bool
    {
        return $this->get($key)->getRender();
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
    public function setDefinitionRender(Stringable|string|float|int $key, bool $render, bool $exception = true): static
    {
        $this->get($key, exception: $exception)->setRender($render);
        return $this;
    }


    /**
     * Direct method to render or not display entries
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $render
     * @param bool                        $exception
     *
     * @return static
     */
    public function setDefinitionDisplay(Stringable|string|float|int $key, bool $render, bool $exception = true): static
    {
        $this->get($key, exception: $exception)->setDisplay($render);
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
    public function setDefinitionSize(Stringable|string|float|int $key, int $size, bool $exception = true): static
    {
        $this->get($key, exception: $exception)->setSize($size);
        return $this;
    }


    /**
     * Direct method to set label for entries
     *
     * @param Stringable|string|float|int $key
     * @param string|null                 $value
     * @param bool                        $exception
     *
     * @return static
     */
    public function setDefinitionLabel(Stringable|string|float|int $key, ?string $value, bool $exception = true): static
    {
        $this->get($key, exception: $exception)->setLabel($value);
        return $this;
    }


    /**
     * Direct method to make entries readonly
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $readonly
     * @param bool                        $exception
     *
     * @return static
     */
    public function setDefinitionReadonly(Stringable|string|float|int $key, bool $readonly, bool $exception = true): static
    {
        $this->get($key, exception: $exception)->setReadonly($readonly);
        return $this;
    }


    /**
     * Direct method to make entries disabled
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $disabled
     * @param bool                        $exception
     *
     * @return static
     */
    public function setDefinitionDisabled(Stringable|string|float|int $key, bool $disabled, bool $exception = true): static
    {
        $this->get($key, exception: $exception)->setDisabled($disabled);
        return $this;
    }


    /**
     * Returns if meta-information is visible at all, or not
     *
     * @return bool
     */
    public function getRenderMeta(): bool
    {
        $render  = false;
        $columns = $this->getDataEntryObject()?->getMetaColumns();

        foreach ($columns as $column) {
            $render = ($render or $this->get($column)?->getRender());
        }

        return $this->render_meta and $render;
    }


    /**
     * Sets if meta-information is visible at all, or not
     *
     * @param bool $render_meta
     *
     * @return static
     */
    public function setRenderMeta(bool $render_meta): static
    {
        $this->render_meta = $render_meta;
        return $this;
    }


    /**
     * Returns the first Definition entry
     *
     * @return DefinitionInterface|null
     */
    #[ReturnTypeWillChange] public function getFirstValue(): ?DefinitionInterface
    {
        return $this->source[array_key_first($this->source)];
    }


    /**
     * Returns the last Definition entry
     *
     * @return DefinitionInterface|null
     */
    #[ReturnTypeWillChange] public function getLastValue(): ?DefinitionInterface
    {
        return $this->source[array_key_last($this->source)];
    }


    /**
     * Removes the definitions column prefix from the specified key and returns it
     *
     * @param string $key
     *
     * @return string
     */
    public function removeColumnPrefix(string $key): string
    {
        return str_replace((string) $this->prefix, '', $key);
    }
}
