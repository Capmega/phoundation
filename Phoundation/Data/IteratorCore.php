<?php

/**
 * Class IteratorCore
 *
 * This is a slightly extended interface to the default PHP iterator class. This class also requires the following
 * methods:
 *
 * - IteratorCore::getCount() Returns the number of elements contained in this object
 *
 * - IteratorCore::getFirst() Returns the first element contained in this object without changing the internal pointer
 *
 * - IteratorCore::getLast() Returns the last element contained in this object without changing the internal pointer
 *
 * - IteratorCore::clear() Clears all the internal content for this object
 *
 * - IteratorCore::delete() Deletes the specified key
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data;

use PDOStatement;
use Phoundation\Cli\Cli;
use Phoundation\Content\Documents\Interfaces\SpreadSheetInterface;
use Phoundation\Content\Documents\SpreadSheet;
use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\DataIterator;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Interfaces\DataIteratorInterface;
use Phoundation\Data\Exception\IteratorException;
use Phoundation\Data\Exception\IteratorKeyExistsException;
use Phoundation\Data\Exception\IteratorKeyNotExistsException;
use Phoundation\Data\Interfaces\ArraySourceInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Traits\TraitDataColumns;
use Phoundation\Data\Traits\TraitDataFilterForm;
use Phoundation\Data\Traits\TraitDataRowCallbacks;
use Phoundation\Data\Traits\TraitDataParent;
use Phoundation\Data\Traits\TraitDataRestrictions;
use Phoundation\Data\Traits\TraitDataSourceArray;
use Phoundation\Databases\Sql\Limit;
use Phoundation\Exception\NotExistsException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Utils\Utils;
use Phoundation\Web\Html\Components\Input\InputSelect;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;
use Phoundation\Web\Html\Components\P;
use Phoundation\Web\Html\Components\Tables\HtmlDataTable;
use Phoundation\Web\Html\Components\Tables\HtmlTable;
use Phoundation\Web\Html\Components\Tables\Interfaces\HtmlDataTableInterface;
use Phoundation\Web\Html\Components\Tables\Interfaces\HtmlTableInterface;
use Phoundation\Web\Html\Enums\EnumTableIdColumn;
use ReturnTypeWillChange;
use Stringable;
use Throwable;


class IteratorCore extends IteratorBase implements IteratorInterface
{
    use TraitDataColumns;
    use TraitDataFilterForm;
    use TraitDataParent {
        setParentObject as protected __setParent;
    }
    use TraitDataRestrictions;
    use TraitDataRowCallbacks;
    use TraitDataSourceArray;


    /**
     * Tracks the datatype required for all elements in this iterator, NULL if none is required
     *
     * @var array|null
     */
    protected ?array $accepted_data_types = null;

    /**
     * Tracks the class used to generate the select input
     *
     * @var string
     */
    protected string $input_select_class = InputSelect::class;


    /**
     * Returns the first data type that is allowed and accepted for this data iterator, considered as the most important
     * one
     *
     * @return string|null
     */
    public function getAcceptedDataType(): ?string
    {
        return array_value_first($this->getAcceptedDataTypes());
    }


    /**
     * Returns the data types that are allowed and accepted for this data iterator
     *
     * @return string|null
     */
    public static function getDefaultContentDataType(): ?string
    {
        return 'mixed';
    }


    /**
     * Returns the class used to generate the select input
     *
     * @return string
     */
    public function getInputSelectClass(): string
    {
        return $this->input_select_class;
    }


    /**
     * Sets the class used to generate the select input
     *
     * @param string $input_select_class
     *
     * @return DataIterator
     */
    public function setComponentClass(string $input_select_class): static
    {
        if (is_a($input_select_class, InputSelectInterface::class, true)) {
            $this->input_select_class = $input_select_class;

            return $this;
        }

        throw new OutOfBoundsException(tr('Cannot use specified class ":class" to generate input select, the class must be an instance of InputSelectInterface', [
            ':class' => $input_select_class,
        ]));
    }


    /**
     * Sets the parent
     *
     * @param DataEntryInterface $parent
     *
     * @return static
     */
    public function setParentObject(DataEntryInterface $parent): static
    {
        // Clear the source to avoid having a parent with the wrong children
        $this->source = [];

        return $this->__setParent($parent);
    }


    /**
     * Returns the current entry
     *
     * @note overrides the IteratorBase::current() method which does not support the protected Iterator||ensureobject()
     *       method
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function current(): mixed
    {
        return $this->ensureObject(key($this->source));
    }


    /**
     * Wrapper for Iterator::append()
     *
     * @param mixed                      $value
     * @param Stringable|string|int|null $key
     * @param bool                       $skip_null_values
     * @param bool                       $exception
     *
     * @return static
     */
    public function add(mixed $value, Stringable|string|int|null $key = null, bool $skip_null_values = true, bool $exception = true): static
    {
        return $this->append($value, $key, $skip_null_values, $exception);
    }


    /**
     * Add the specified value to the iterator array using an optional key
     *
     * @note if no key was specified, the entry will be assigned as-if a new array entry
     *
     * @param mixed                      $value
     * @param Stringable|string|int|null $key
     * @param bool                       $skip_null_values
     * @param bool                       $exception
     *
     * @return static
     */
    public function append(mixed $value, Stringable|string|int|null $key = null, bool $skip_null_values = true, bool $exception = true): static
    {
        // Skip NULL values?
        if ($value === null) {
            if ($skip_null_values) {
                return $this;
            }
        }

        $this->checkDataType($value);

        // NULL keys will be added as numerical "next" entries
        if ($key === null) {
            $key = $this->fetchKeyFromValue($value);
        }

        if ($key === null) {
            $this->source[] = $value;

        } else {
            if (array_key_exists($key, $this->source) and $exception) {
                throw new IteratorKeyExistsException(tr('Cannot add key ":key" to Iterator class ":class" object because the key already exists', [
                    ':key'   => $key,
                    ':class' => get_class($this),
                ]));
            }

            $this->source[$key] = $value;
        }

        return $this;
    }


    /**
     * Check if the datatype of the given value or Interface (in case of an object) is allowed
     *
     * Throws an OutOfBounds exception if the datatype or Interface is not allowed
     *
     * @param mixed $value
     *
     * @return void
     */
    protected function checkDataType(mixed $value): void
    {
        if (!$this->accepted_data_types) {
            return;
        }

        foreach ($this->accepted_data_types as $data_type) {
            if ($data_type === 'mixed') {
                // This accepts everything
                return;

            } elseif (str_contains($data_type, '\\')) {
                if ($value instanceof $data_type) {
                    return;
                }

            } else {
                if (gettype($value) === $data_type) {
                    return;
                }
            }
        }

        throw new OutOfBoundsException(tr('Value argument must be of type ":allowed", ":type" given', [
            ':type'    => (is_object($value) ? get_class($value) : gettype($value)),
            ':allowed' => Strings::force($this->accepted_data_types, ', '),
        ]));
    }


    /**
     * Forces the specified source to become an Iterator
     *
     * @note DataIterator objects will remain DataIterator objects as those are extended Iterators
     *
     * @param mixed       $source
     * @param string|null $separator
     *
     * @return IteratorInterface|DataIteratorInterface
     */
    public static function force(mixed $source, ?string $separator = ','): IteratorInterface|DataIteratorInterface
    {
        if (($source === '') or ($source === null)) {
            return new Iterator();
        }

        if ($source instanceof IteratorInterface) {
            // This already is an Iterator (or DataIterator) object
            return $source;
        }

        if (is_string($source)) {
            // Explode the string to an Iterator object, as we would to an array as well
            return static::explode($separator, $source);
        }

        if ($source instanceof ArrayableInterface) {
            // This is an object that can convert to array. Extract the array
            $source = $source->__toArray();

        } else {
            // Unknown datatype, toss it into an array
            $source = [$source];
        }

        // As of here, we have an array. Set it as an Iterator source and return
        return Iterator::new()->setSource($source);
    }


    /**
     * Explodes the specified string into an Iterator object and returns it
     *
     * @param Stringable|string $source
     * @param string|null       $separator
     *
     * @return IteratorInterface
     */
    public static function explode(Stringable|string $source, ?string $separator = ','): IteratorInterface
    {
        $source = (string) $source;

        if ($separator) {
            $source = explode($separator, $source);

        } else {
            // We cannot explode with an empty separator, assume that $source is a single item and return it as such
            $source = [$source];
        }

        return Iterator::new()->setSource($source);
    }


    /**
     * Add the specified value to the iterator array using an optional key
     *
     * @note if no key was specified, the entry will be assigned as-if a new array entry
     *
     * @param mixed                      $value
     * @param Stringable|string|int|null $key
     * @param bool                       $skip_null_values
     * @param bool                       $exception
     *
     * @return static
     */
    public function prepend(mixed $value, Stringable|string|int|null $key = null, bool $skip_null_values = true, bool $exception = true): static
    {
        // Skip NULL values?
        if ($value === null) {
            if ($skip_null_values) {
                return $this;
            }
        }

        $this->checkDataType($value);

        // NULL keys will be added as numerical "first" entries
        if ($key === null) {
            array_unshift($this->source, $value);

        } else {
            if (array_key_exists($key, $this->source) and $exception) {
                throw new IteratorKeyExistsException(tr('Cannot add key ":key" to Iterator class ":class" object because the key already exists', [
                    ':key'   => $key,
                    ':class' => get_class($this),
                ]));
            }

            $this->source = array_merge([$key => $value], $this->source);
        }

        return $this;
    }


    /**
     * Add the specified value to the iterator array using an optional key BEFORE the specified $before_key
     *
     * @note if no key was specified, the entry will be assigned as-if a new array entry
     *
     * @param mixed                      $value
     * @param Stringable|string|int|null $key
     * @param Stringable|string|int|null $before
     * @param bool                       $skip_null_values
     * @param bool                       $exception
     *
     * @return static
     */
    public function prependBeforeKey(mixed $value, Stringable|string|int|null $key = null, Stringable|string|int|null $before = null, bool $skip_null_values = true, bool $exception = true): static
    {
        // Skip NULL values?
        if ($value === null) {
            if ($skip_null_values) {
                return $this;
            }
        }

        $this->checkDataType($value);

        // Ensure the before key exists
        if (!array_key_exists($before, $this->source)) {
            throw new IteratorKeyNotExistsException(tr('Cannot add key ":key" to Iterator class ":class" object before key ":before" because the before key ":before" does not exist', [
                ':key'    => $key,
                ':before' => $before,
                ':class'  => get_class($this),
            ]));
        }

        // NULL keys will be added as numerical "next" entries
        if (array_key_exists($key, $this->source) and $exception) {
            throw new IteratorKeyExistsException(tr('Cannot add key ":key" to Iterator class ":class" object before key ":before" because the key ":key" already exists', [
                ':key'    => $key,
                ':before' => $before,
                ':class'  => get_class($this),
            ]));
        }

        Arrays::spliceByKey($this->source, $before, 0, [$key => $value], false);

        return $this;
    }


    /**
     * Same as Arrays::spliceKey() but for this Iterator
     *
     * @param string                  $key
     * @param int|null                $length
     * @param IteratorInterface|array $replacement
     * @param bool                    $after
     * @param array|null              $spliced
     *
     * @return static
     */
    public function spliceByKey(string $key, ?int $length = null, IteratorInterface|array $replacement = [], bool $after = false, array &$spliced = null): static
    {
        try {
            $spliced = Arrays::spliceByKey($this->source, $key, $length, $replacement, $after);

        } catch (OutOfBoundsException $e) {
            throw new OutOfBoundsException(tr('Failed to splice iterator by key ":key", the key does not exist', [
                ':key' => $key,
            ]), $e);
        }

        return $this;
    }


    /**
     * Add the specified value to the iterator array using an optional key AFTER the specified $after_key
     *
     * @note if no key was specified, the entry will be assigned as-if a new array entry
     *
     * @param mixed                      $value
     * @param Stringable|string|int|null $key
     * @param Stringable|string|int|null $after
     * @param bool                       $skip_null_values
     * @param bool                       $exception
     *
     * @return static
     */
    public function appendAfterKey(mixed $value, Stringable|string|int|null $key = null, Stringable|string|int|null $after = null, bool $skip_null_values = true, bool $exception = true): static
    {
        // Skip NULL values?
        if ($value === null) {
            if ($skip_null_values) {
                return $this;
            }
        }

        $this->checkDataType($value);

        // Ensure the after key exists
        if (!array_key_exists($after, $this->source)) {
            throw new IteratorKeyNotExistsException(tr('Cannot add key ":key" to Iterator class ":class" object after key ":after" because the after key ":after" does not exist', [
                ':key'   => $key,
                ':after' => $after,
                ':class' => get_class($this),
            ]));
        }

        // NULL keys will be added as numerical "next" entries
        if (array_key_exists($key, $this->source) and $exception) {
            throw new IteratorKeyExistsException(tr('Cannot add key ":key" to Iterator class ":class" object after key ":after" because the key ":key" already exists', [
                ':key'   => $key,
                ':after' => $after,
                ':class' => get_class($this),
            ]));
        }

        Arrays::spliceByKey($this->source, $after, 0, [$key => $value], true);

        return $this;
    }


    /**
     * Add the specified value to the iterator array using an optional key BEFORE the specified $before_value
     *
     * @note if no key was specified, the entry will be assigned as-if a new array entry
     *
     * @param mixed                      $value
     * @param Stringable|string|int|null $key
     * @param mixed                      $before
     * @param bool                       $strict
     * @param bool                       $skip_null_values
     * @param bool                       $exception
     *
     * @return static
     */
    public function prependBeforeValue(mixed $value, Stringable|string|int|null $key = null, mixed $before = null, bool $strict = false, bool $skip_null_values = true, bool $exception = true): static
    {
        // Skip NULL values?
        if ($value === null) {
            if ($skip_null_values) {
                return $this;
            }
        }

        $this->checkDataType($value);

        // Ensure the before key exists
        $before_key = array_search($before, $this->source, $strict);

        if ($before_key === false) {
            throw new IteratorKeyNotExistsException(tr('Cannot add key ":key" to Iterator class ":class" object before value ":before" because the before value ":before" does not exist', [
                ':key'    => $key,
                ':before' => $before,
                ':class'  => get_class($this),
            ]));
        }

        // NULL keys will be added as numerical "next" entries
        if (array_key_exists($key, $this->source) and $exception) {
            throw new IteratorKeyExistsException(tr('Cannot add key ":key" to Iterator class ":class" object before key ":before" because the key ":key" already exists', [
                ':key'    => $key,
                ':before' => $before,
                ':class'  => get_class($this),
            ]));
        }

        Arrays::spliceByKey($this->source, $before_key, 0, [$key => $value], false);

        return $this;
    }


    /**
     * Add the specified value to the iterator array using an optional key AFTER the specified $after_value
     *
     * @note if no key was specified, the entry will be assigned as-if a new array entry
     *
     * @param mixed                      $value
     * @param Stringable|string|int|null $key
     * @param mixed                      $after
     * @param bool                       $strict
     * @param bool                       $skip_null_values
     * @param bool                       $exception
     *
     * @return static
     */
    public function appendAfterValue(mixed $value, Stringable|string|int|null $key = null, mixed $after = null, bool $strict = false, bool $skip_null_values = true, bool $exception = true): static
    {
        // Skip NULL values?
        if ($value === null) {
            if ($skip_null_values) {
                return $this;
            }
        }

        $this->checkDataType($value);

        // Ensure the after value exists
        $after_key = array_search($after, $this->source, $strict);

        if ($after_key === false) {
            throw new IteratorKeyNotExistsException(tr('Cannot add key ":key" to Iterator class ":class" object after value ":after" because the after value ":after" does not exist', [
                ':key'   => $key,
                ':after' => $after,
                ':class' => get_class($this),
            ]));
        }

        // NULL keys will be added as numerical "next" entries
        if (array_key_exists($key, $this->source) and $exception) {
            throw new IteratorKeyExistsException(tr('Cannot add key ":key" to Iterator class ":class" object after key ":after" because the key ":key" already exists', [
                ':key'   => $key,
                ':after' => $after,
                ':class' => get_class($this),
            ]));
        }

        Arrays::spliceByKey($this->source, $after_key, 0, [$key => $value], true);

        return $this;
    }


    /**
     * Will remove the entry with the specified key before the $before key
     *
     * @param Stringable|string|int|null $key
     * @param Stringable|string|int|null $before
     * @param bool                       $strict
     *
     * @return static
     */
    public function moveBeforeKey(Stringable|string|int|null $key, Stringable|string|int|null $before, bool $strict = true): static
    {
        $pos_key    = array_search($key   , array_keys($this->source), $strict);
        $pos_before = array_search($before, array_keys($this->source), $strict);

        if ($pos_key === false) {
            throw new OutOfBoundsException(tr('Specified key ":key" does not exist in this ":class" list', [
                ':key'   => $key,
                ':class' => get_class($this),
            ]));
        }

        if ($pos_before === false) {
            throw new OutOfBoundsException(tr('Specified before key ":key" does not exist in this ":class" list', [
                ':key'   => $before,
                ':class' => get_class($this),
            ]));
        }

        $part1 = array_splice($this->source, $pos_key, 1);
        $part2 = array_splice($this->source, 0, $pos_before);

        $this->source = array_merge($part2, $part1, $this->source);

        return $this;
    }


    /**
     * Will remove the entry with the specified key after the $after key
     *
     * @param Stringable|string|int|null $key
     * @param Stringable|string|int|null $after
     * @param bool                       $strict
     *
     * @return static
     */
    public function moveAfterKey(Stringable|string|int|null $key, Stringable|string|int|null $after, bool $strict = true): static
    {
        $pos_key   = array_search($key  , array_keys($this->source), $strict);
        $pos_after = array_search($after, array_keys($this->source), $strict);

        if ($pos_key === false) {
            throw new OutOfBoundsException(tr('Specified key ":key" does not exist in this ":class" list', [
                ':key'   => $key,
                ':class' => get_class($this),
            ]));
        }

        if ($pos_after === false) {
            throw new OutOfBoundsException(tr('Specified after key ":key" does not exist in this ":class" list', [
                ':key'   => $after,
                ':class' => get_class($this),
            ]));
        }

        $part1 = array_splice($this->source, $pos_key, 1);
        $part2 = array_splice($this->source, 0, $pos_after + 1);

        $this->source = array_merge($part2, $part1, $this->source);

        return $this;
    }


    /**
     * Copies the value of the specified $from_key to the specified $to_key
     *
     * Note: $from_key must exist, or an OutOfBoundsException will be thrown
     * Note: If the specified key $to_key already exist, its value will be overwritten
     *
     * @param Stringable|string|int|null $from_key
     * @param Stringable|string|int|null $to_key
     *
     * @return static
     * @throws OutOfBoundsException|Throwable
     */
    public function copyValue(Stringable|string|int|null $from_key, Stringable|string|int|null $to_key): static
    {
        try {
            $this->source[$to_key] = $this->source[$from_key];

        } catch (Throwable $e) {
            if (array_key_exists($from_key, $this->source)) {
                // No idea what went wrong here
                throw $e;
            }

            throw new OutOfBoundsException(tr('The specified from_key ":from_key" does not exist in this ":class" Iterator', [
                ':from_key' => $from_key,
                ':class'    => static::class
            ]), $e);
        }

        return $this;
    }


    /**
     * Adds the specified source(s) to the internal source
     *
     * @param IteratorInterface|array|string|null $source
     * @param bool                                $clear_keys
     * @param bool                                $exception
     *
     * @return static
     */
    public function addSource(IteratorInterface|array|string|null $source, bool $clear_keys = false, bool $exception = true): static
    {
        if ($source instanceof IteratorInterface) {
            if ($source === $this) {
                throw OutOfBoundsException::new(tr('Cannot add a source Iterator object that is itself, to itself, it would cause an endless loop'))
                                          ->addData([
                                              'this'   => $this,
                                              'source' => $source,
                                          ]);
            }

            $source = $source->getSource();
        }

        // Add each entry
        foreach (Arrays::force($source) as $key => $value) {
            $this->add($value, $clear_keys ? null : $key, exception: $exception);
        }

        return $this;
    }


    /**
     * Sets the internal source directly, but separating the values in key > values by $separator
     *
     * Any value that is NOT a string will be quietly ignored
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null                                       $execute
     * @param string|null                                      $separator
     * @return static
     */
    public function setKeyValueSource(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null, ?string $separator = null): static
    {
        $source = Arrays::extractSourceArray($source, $execute);

        if ($separator) {
            foreach ($source as $key => &$value) {
                if (is_string($value)) {
                    $key   = trim(Strings::until($value, $separator));
                    $value = trim(Strings::from($value, $separator));

                    $this->source[$key] = $value;
                }
            }

            unset($value);

        } else {
            $this->source = $source;
        }

        return $this;
    }


    /**
     * Append the specified source to the end of this Iterator
     *
     * @param IteratorInterface|array ...$sources
     *
     * @return static
     */
    public function appendSource(IteratorInterface|array ...$sources): static
    {
        foreach ($sources as $source) {
            if (is_object($source)) {
                $source = $source->__toArray();
            }

            $this->source = array_merge($this->source, $source);
        }

        return $this;
    }


    /**
     * Prepend the specified source at the beginning of this Iterator
     *
     * @param IteratorInterface|array ...$sources
     *
     * @return static
     */
    public function prependSource(IteratorInterface|array ...$sources): static
    {
        foreach ($sources as $source) {
            if (is_object($source)) {
                $source = $source->__toArray();
            }

            $this->source = array_merge($source, $this->source);
        }

        return $this;
    }


    /**
     * Returns the datatype restrictions for all elements in this iterator, NULL if none
     *
     * @return array|null
     */
    public function getAcceptedDataTypes(): ?array
    {
        return $this->accepted_data_types;
    }


    /**
     * Sets the datatype restrictions for all elements in this iterator, NULL if none
     *
     * @param array|string|null $data_types
     * @return static
     */
    public function setAcceptedDataTypes(array|string|null $data_types): static
    {
        $data_types = Arrays::force($data_types, '|');

        foreach ($data_types as $data_type) {
            if ($data_type) {
                if (!preg_match('/^mixed|bool|int|float|string|array|null|resource|object|(?:(?:(?:(?:[A-Z][a-z0-9]+)+\\\)+)+(?:[A-Z][a-z0-9]+)+)$/', $data_type)) {
                    throw new OutOfBoundsException(tr('Invalid Iterator datatype restriction ":datatype" specified, must be one or multiple of "int|array|string|Class\Path"', [
                        ':datatype' => $data_type,
                    ]));
                }

            } else {
                // No data type restrictions required
                $data_type = null;
            }
        }

        $this->accepted_data_types = $data_types;

        return $this;
    }


    /**
     * Returns value for the specified key, defaults that key to the specified value if it does not yet exist
     *
     * @param Stringable|string|int $key
     * @param mixed                       $value
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function getValueOrDefault(Stringable|string|int $key, mixed $value): mixed
    {
        if (!array_key_exists($key, $this->source)) {
            $this->source[$key] = $value;
        }

        return $this->source[$key];
    }


    /**
     * Returns the first element contained in this object without changing the internal pointer
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function getFirstValue(): mixed
    {
        if (empty($this->source)) {
            return null;
        }

        return $this->ensureObject(array_key_first($this->source));
    }


    /**
     * Returns the last element contained in this object without changing the internal pointer
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function getLastValue(): mixed
    {
        if (empty($this->source)) {
            return null;
        }

        return $this->ensureObject(array_key_last($this->source));
    }


    /**
     * Returns if the specified key exists or not
     *
     * @param Stringable|string|int $key
     *
     * @return bool
     */
    public function keyExists(Stringable|string|int $key): bool
    {
        if (is_object($key)) {
            $key = (string) $key;
        }

        return array_key_exists($key, $this->source);
    }


    /**
     * Returns if the specified value exists in this Iterator or not
     *
     * @note Wrapper for IteratorCore::exists()
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function valueExists(mixed $value): bool
    {
        return in_array($value, $this->source);
    }


    /**
     * Returns the length of the longest value
     *
     * @return int
     */
    public function getLongestKeyLength(): int
    {
        return Arrays::getLongestKeyLength($this->source);
    }


    /**
     * Returns the length of the shortest value
     *
     * @return int
     */
    public function getShortestKeyLength(): int
    {
        return Arrays::getShortestKeyLength($this->source);
    }


    /**
     * Returns the length of the longest value
     *
     * @param string|null $key
     * @param bool        $exception
     *
     * @return int
     */
    public function getLongestValueLength(?string $key = null, bool $exception = false): int
    {
        return Arrays::getLongestValueLength($this->source, $key, $exception);
    }


    /**
     * Returns the length of the shortest value
     *
     * @param string|null $key
     * @param bool        $exception
     *
     * @return int
     */
    public function getShortestValueLength(?string $key = null, bool $exception = false): int
    {
        return Arrays::getShortestValueLength($this->source, $key, $exception);
    }


    /**
     * Remove source keys on the specified needles with the specified match mode
     *
     * @param ArrayableInterface|array|string|int|null $needles
     * @param int                                      $flags
     *
     * @return static
     */
    public function keepMatchingKeys(ArrayableInterface|array|string|int|null $needles, int $flags = Utils::MATCH_FULL | Utils::MATCH_REQUIRE): static
    {
        $this->source = Arrays::keepMatchingKeys($this->source, $needles, $flags);
        return $this;
    }


    /**
     * Keep source values on the specified needles with the specified match mode
     *
     * @param ArrayableInterface|array|string|int|null $needles
     * @param int                                      $flags
     * @param string|null                              $column
     *
     * @return static
     */
    public function keepMatchingValues(ArrayableInterface|array|string|int|null $needles, int $flags = Utils::MATCH_FULL | Utils::MATCH_REQUIRE, ?string $column = null): static
    {
        $this->source = Arrays::keepMatchingValues($this->source, $needles, $flags, $column);
        return $this;
    }


    /**
     * Remove source keys on the specified needles with the specified match mode
     *
     * @param ArrayableInterface|array|string|int|null $needles
     * @param int                                      $flags
     *
     * @return static
     */
    public function removeMatchingKeys(ArrayableInterface|array|string|int|null $needles, int $flags = Utils::MATCH_FULL | Utils::MATCH_REQUIRE): static
    {
        $this->source = Arrays::removeMatchingKeys($this->source, $needles, $flags);
        return $this;
    }


    /**
     * Remove source values on the specified needles with the specified match mode
     *
     * @param ArrayableInterface|array|string|int|null $needles
     * @param int                                      $flags
     * @param string|null                              $column
     *
     * @return static
     */
    public function removeMatchingValues(ArrayableInterface|array|string|int|null $needles, int $flags = Utils::MATCH_FULL | Utils::MATCH_REQUIRE, ?string $column = null): static
    {
        $this->source = Arrays::removeMatchingValues($this->source, $needles, $flags, $column);
        return $this;
    }


    /**
     * Returns Iterator with the entries where the keys match the specified needles and flags
     *
     * @param ArrayableInterface|array|string|int|null $needles
     * @param int                                      $flags
     *
     * @return static
     */
    public function getMatchingKeys(ArrayableInterface|array|string|int|null $needles, int $flags = Utils::MATCH_FULL | Utils::MATCH_REQUIRE): IteratorInterface
    {
        return new Iterator(Arrays::keepMatchingKeys($this->source, $needles, $flags));
    }


    /**
     * Returns Iterator with the entries where the values match the specified needles and flags
     *
     * @param ArrayableInterface|array|string|int|null $needles
     * @param int                                      $flags
     *
     * @return static
     */
    public function getMatchingValues(ArrayableInterface|array|string|int|null $needles, int $flags = Utils::MATCH_FULL | Utils::MATCH_REQUIRE): IteratorInterface
    {
        return new Iterator(Arrays::keepMatchingValues($this->source, $needles, $flags));
    }


    /**
     * Returns a list with all the values that match the specified value
     *
     * @param ArrayableInterface|array|string|int|null $needles
     * @param int                                      $flags
     *
     * @return IteratorInterface
     */
    public function keepMatchingValuesStartingWith(ArrayableInterface|array|string|int|null $needles, int $flags = Utils::MATCH_CASE_INSENSITIVE | Utils::MATCH_ALL | Utils::MATCH_STARTS_WITH, ?string $column = null): IteratorInterface
    {
        return new Iterator(Arrays::keepMatchingValuesStartingWith($this->source, $needles, $flags, $column));
    }


    /**
     * Returns a list with all the values that match the specified value
     *
     * @param ArrayableInterface|array|string|int|null $needles
     * @param int                                      $flags
     *
     * @return IteratorInterface
     */
    public function keepMatchingKeysStartingWith(ArrayableInterface|array|string|int|null $needles, int $flags = Utils::MATCH_CASE_INSENSITIVE | Utils::MATCH_ALL | Utils::MATCH_STARTS_WITH): IteratorInterface
    {
        return new Iterator(Arrays::keepMatchingKeysStartingWith($this->source, $needles, $flags));
    }


//    /**
//     * Deletes the entries that have columns with the specified value(s)
//     *
//     * @param Stringable|array|string|int $values
//     * @param string                            $column
//     *
//     * @return static
//     */
//    public function removeMatchingValuesByColumn(Stringable|array|string|int $values, string $column): static
//    {
//        foreach (Arrays::force($values, null) as $value) {
//            foreach ($this->source as $key => $data) {
//                if (is_array($data)) {
//                    if (!array_key_exists($column, $data)) {
//                        throw new OutOfBoundsException(tr('Cannot delete entries by column ":column" value ":value" because entry ":key" does not have the requested column ":column"', [
//                            ':key'    => $key,
//                            ':value'  => $value,
//                            ':column' => $column,
//                        ]));
//                    }
//                    if ($data[$key] === $value) {
//                        unset($this->source[$key]);
//                    }
//                } else {
//                    if (!$data instanceof DataEntry) {
//                        throw new OutOfBoundsException(tr('Cannot delete entries by column ":column" value ":value" because key ":key" is neither array nor DataEntry', [
//                            ':key'    => $key,
//                            ':value'  => $value,
//                            ':column' => $column,
//                        ]));
//                    }
//                    // This entry is not an array but DataEntry object. Compare using DataEntry::get()
//                    if ($data->load($key) === $value) {
//                        unset($this->source[$key]);
//                    }
//                }
//            }
//        }
//
//        return $this;
//    }


    /**
     * Returns the total amounts for all columns together
     *
     * @param array|string $columns
     * @param string|null $totals_column
     * @return array
     */
    public function getTotals(array|string $columns, ?string $totals_column = null): array
    {
        if (is_string($columns)) {
            $columns = Arrays::force($columns);
            $columns = Arrays::initialize($columns, 'total');
        }

        if (!$this->source) {
            return array_keys($columns);
        }

        // Get the first entry to use for columns, and remove the ID column
        $entry = $this->getFirstValue();

        // If the first entry is ArraySourceInterface object, get the source data array
        if (is_object($entry)) {
            if (!($entry instanceof ArraySourceInterface)) {
                throw new OutOfBoundsException(tr('Cannot generate totals, first entry ":entry" is a non ArraySourceInterface object', [
                    ':entry' => get_class($entry)
                ]));
            }

            $entry = $entry->getSource();
        }

        array_shift($entry);

        // Build up the return columns
        $key   = array_key_first($entry);
        $entry = Arrays::setValues($entry, null);
        $entry = array_merge($entry, [$totals_column ?? $key => tr('Totals')]);

        foreach ($columns as $column => $total) {
            $return[$column] = 0;
        }

        // Build up the totals
        foreach ($this->source as &$entry) {
            // If the entry is ArraySourceInterface object, get the source data array
            if (is_object($entry)) {
                if (!($entry instanceof ArraySourceInterface)) {
                    throw new OutOfBoundsException(tr('Cannot generate totals, entry ":entry" is a non ArraySourceInterface object', [
                        ':entry' => get_class($entry)
                    ]));
                }

                $entry = $entry->getSource();
            }

            if (!is_array($entry)) {
                throw new OutOfBoundsException(tr('Cannot generate source totals, source contains non-array entry ":entry"', [
                    ':entry' => get_class($entry),
                ]));
            }

            foreach ($columns as $column => $total) {
                if (!array_key_exists($column, $entry)) {
                    continue;
                }

                // Get data from array
                if ($total) {
                    if (array_key_exists($column, $return)) {
                        $return[$column] += get_numeric($entry[$column]);

                    } else {
                        $return[$column] = get_numeric($entry[$column]);
                    }

                } else {
                    $return[$column] = null;
                }
            }
        }

        return $return;
    }


    /**
     * Displays a message on the command line
     *
     * @param string|null $message
     * @param bool        $header
     *
     * @return static
     */
    public function displayCliMessage(?string $message = null, bool $header = false): static
    {
        if ($header) {
            Log::information($message, echo_prefix: false);

        } else {
            Log::cli($message);
        }

        return $this;
    }


    /**
     * Creates and returns a CLI table for the data in this list
     *
     * @param array|string|null $columns
     * @param array             $filters
     * @param string|null       $id_column
     *
     * @return static
     */
    public function displayCliTable(array|string|null $columns = null, array $filters = [], ?string $id_column = 'id'): static
    {
        Cli::displayTable($this->source, $columns, $id_column);
        return $this;
    }


    /**
     * Creates and returns a CLI key-value table for the data in this list
     *
     * @param string|null $key_header
     * @param string|null $value_header
     * @param int         $offset
     *
     * @return static
     */
    public function displayCliKeyValueTable(?string $key_header = null, string $value_header = null, int $offset = 0): static
    {
        Cli::displayForm($this->source, $key_header, $value_header, $offset);
        return $this;
    }


    /**
     * Shift an entry off the beginning of this Iterator
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function shift(): mixed
    {
        return array_shift($this->source);
    }


    /**
     * Prepend elements to the beginning of an array
     *
     * @return mixed
     */
    public function unshift(mixed ...$values): static
    {
        array_unshift($this->source, $values);
        return $this;
    }


    /**
     * Sorts the Iterator source in ascending order
     *
     * @return static
     */
    public function sort(): static
    {
        asort($this->source);
        return $this;
    }


    /**
     * Sorts the Iterator source in descending order
     *
     * @return static
     */
    public function rsort(): static
    {
        arsort($this->source);
        return $this;
    }


    /**
     * Sorts the Iterator source keys in ascending order
     *
     * @return static
     */
    public function ksort(): static
    {
        ksort($this->source);
        return $this;
    }


    /**
     * Sorts the Iterator source keys in descending order
     *
     * @return static
     */
    public function krsort(): static
    {
        krsort($this->source);
        return $this;
    }


    /**
     * Sorts the Iterator source using the specified callback
     *
     * @param callable $callback
     *
     * @return static
     */
    public function uasort(callable $callback): static
    {
        uasort($this->source, $callback);
        return $this;
    }


    /**
     * Sorts the Iterator source keys using the specified callback
     *
     * @param callable $callback
     *
     * @return static
     */
    public function uksort(callable $callback): static
    {
        uksort($this->source, $callback);
        return $this;
    }


    /**
     * Will limit the number of entries in the source of this DataIterator to the
     *
     * @return static
     */
    public function limitAutoComplete(): static
    {
        $this->source = Arrays::limit($this->source, Limit::shellAutoCompletion());
        return $this;
    }


    /**
     * Returns if all (or optionally any) of the specified entries are in this list
     *
     * @param IteratorInterface|array|string $list
     * @param bool                           $all
     * @param string|null                    $always_match
     *
     * @return bool
     */
    public function containsKeys(IteratorInterface|array|string $list, bool $all = true, string $always_match = null): bool
    {
        foreach (Arrays::force($list) as $key) {
            if (!array_key_exists($key, $this->source)) {
                if ($all) {
                    // All need to be in the array, but we found one missing.
                    // Can still match if $always_match is available!
                    if ($always_match and array_key_exists($always_match, $this->source)) {
                        // Okay, this list contains ALL the requested entries due to $always_match
                        return true;
                    }

                    return false;
                }

            } elseif (!$all) {
                // only one needs to be in the array, we found one, we're good!
                return true;
            }
        }

        // All were in the array
        return true;
    }


    /**
     * Returns a list of items that are specified, but not available in this Iterator
     *
     * @param IteratorInterface|array|string $list
     * @param string|null                    $always_match
     *
     * @return array
     * @todo Redo this with array_diff()
     */
    public function getMissingKeys(IteratorInterface|array|string $list, string $always_match = null): array
    {
        $return = [];

        foreach (Arrays::force($list) as $key) {
            if (array_key_exists($key, $this->source)) {
                continue;
            }

            // Can still match if $always_match is available!
            if ($always_match and array_key_exists($always_match, $this->source)) {
                // Okay, this list contains ALL the requested entries due to $always_match
                return [];
            }

            $return[] = $key;
        }

        return $return;
    }


    /**
     * Returns multiple column values for a single entry
     *
     * @param Stringable|string|int $key
     * @param array|string          $columns
     * @param bool                  $exception
     *
     * @return IteratorInterface
     */
    #[ReturnTypeWillChange] public function getSingleRowMultipleColumns(Stringable|string|int $key, array|string $columns, bool $exception = true): IteratorInterface
    {
        if (!$columns) {
            throw new OutOfBoundsException(tr('Cannot return source key columns for ":this", no columns specified', [
                ':this' => get_class($this),
            ]));
        }

        $value = $this->get($key, $exception);
        $value = $this->ensureSourceValueHasColumns($value, $columns);

        return new Iterator(Arrays::keepKeys($value, $columns));
    }


    /**
     * Returns value for the specified key
     *
     * @param Stringable|string|float|int $key $key
     * @param bool                  $exception
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, bool $exception = true): mixed
    {
        // Does this entry exist?
        if (array_key_exists($key, $this->source)) {
            return $this->ensureObject($key);
        }

        if ($exception) {
            // The key does not exist
            throw new NotExistsException(tr('The key ":key" does not exist in this ":class" object', [
                ':key'   => $key,
                ':class' => get_class($this),
            ]));
        }

        return null;
    }


    /**
     * Sets the value for the specified key
     *
     * @note this is basically a wrapper function for IteratorCore::add($value, $key, false) that always requires a key
     *
     * @param mixed                       $value
     * @param Stringable|string|float|int $key
     *
     * @return mixed
     */
    public function set(mixed $value, Stringable|string|float|int $key): static
    {
        return $this->append($value, $key, false, false);
    }


    /**
     * Returns a random entry
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function getRandom(): mixed
    {
        if (empty($this->source)) {
            return null;
        }

        return $this->ensureObject(array_rand($this->source, 1));
    }


    /**
     * Returns a static object with multiple random entries
     *
     * @param int $count
     *
     * @return static
     */
    public function getRandomList(int $count = 1): static
    {
        $iterator = new static();

        return $iterator->setSource(Arrays::getRandomValues($this->source, $count));
    }


    /**
     * Checks that the specified value has the requested columns
     *
     * @param mixed        $value
     * @param array|string $columns
     *
     * @return array|null
     *
     * @throws OutOfBoundsException When the specified value is neither an array nor an ArrayableInterface object
     */
    protected function checkSourceValueHasColumns(ArrayableInterface|array $value, array|string $columns): ?array
    {
        // Ensure we have arrays
        if (is_object($value)) {
            if (!$value instanceof ArrayableInterface) {
                throw new OutOfBoundsException(tr('Cannot get source columns for ":this", the source contains non arrayable objects', [
                    ':this' => get_class($this),
                ]));
            }

            $value = $value->__toArray();
        }

        foreach (Arrays::force($columns) as $column) {
            if (!array_key_exists($column, $value)) {
                throw new OutOfBoundsException(tr('The requested column ":column" does not exist', [
                    ':column' => $column,
                ]));
            }
        }

        return $value;
    }


    /**
     * Ensures that the specified value has the requested columns
     *
     * @param mixed        $value
     * @param array|string $columns
     * @param mixed|null   $default
     *
     * @return array|null
     *
     */
    protected function ensureSourceValueHasColumns(ArrayableInterface|array $value, array|string $columns, mixed $default = null): ?array
    {
        // Ensure we have arrays
        if (is_object($value)) {
            if (!$value instanceof ArrayableInterface) {
                throw new OutOfBoundsException(tr('Cannot get source columns for ":this", the source contains non arrayable objects', [
                    ':this' => get_class($this),
                ]));
            }

            $value = $value->__toArray();
        }

        foreach (Arrays::force($columns) as $column) {
            if (!array_key_exists($column, $value)) {
                $value[$column] = $default;
            }
        }

        return $value;
    }


    /**
     * Returns multiple column values for multiple entries
     *
     * @param Stringable|string|int $key
     * @param string                $column
     * @param bool                  $exception
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function getSingleRowsSingleColumn(Stringable|string|int $key, string $column, bool $exception = true): mixed
    {
        if (!$column) {
            throw new OutOfBoundsException(tr('Cannot return source key column for ":this", no column specified', [
                ':this' => get_class($this),
            ]));
        }

        $value = $this->get($key, $exception);
        $value = $this->checkSourceValueHasColumns($value, $column);
        $value = Arrays::keepKeys($value, $column);

        return $value[$column];
    }


    /**
     * Returns an array with each value containing a scalar with only the specified column value
     *
     * @note This only works on sources that contains array / DataEntry object values. Any other value will cause an
     *       OutOfBoundsException
     *
     * @param string $column
     * @param bool   $allow_scalar
     *
     * @return IteratorInterface
     */
    public function getAllRowsSingleColumn(string $column, bool $allow_scalar = false): IteratorInterface
    {
        if (!$column) {
            throw new OutOfBoundsException(tr('Cannot return source column for ":this", no column specified', [
                ':this' => get_class($this),
            ]));
        }

        $return = [];

        foreach ($this->source as $key => $value) {
            if (is_scalar($value)) {
                if (!$allow_scalar) {
                    throw new OutOfBoundsException(tr('Encountered scalar value for key ":key" where either array or object is required', [
                        ':key' => $key,
                    ]));
                }

                $return[$key] = $value;

            } else {
                $value        = $this->checkSourceValueHasColumns($value, $column);
                $return[$key] = $value[$column];
            }
        }

        return new Iterator($return);
    }


    /**
     * Same as Arrays::splice() but for this Iterator
     *
     * @param int                     $offset
     * @param int|null                $length
     * @param IteratorInterface|array $replacement
     * @param array|null              $spliced
     *
     * @return static
     */
    public function splice(int $offset, ?int $length = null, IteratorInterface|array $replacement = [], array &$spliced = null): static
    {
        $spliced = Arrays::splice($this->source, $offset, $length, $replacement);

        return $this;
    }


    /**
     * Renames and returns the specified value
     *
     * @param Stringable|string|int $key
     * @param Stringable|string|int $target
     * @param bool                  $exception
     *
     * @return DefinitionInterface
     */
    #[ReturnTypeWillChange] public function renameKey(Stringable|string|int $key, Stringable|string|int $target, bool $exception = true): mixed
    {
        // First, ensure the target doesn't exist yet!
        if (array_key_exists($target, $this->source)) {
            throw new IteratorException(tr('Cannot rename key ":key" to target ":target", the target key already exists', [
                ':key'    => $key,
                ':target' => $target,
            ]));
        }

        // Then, get the entry
        $entry = $this->get($key, $exception);

        // Now rename
        $this->source[$target] = $this->source[$key];
        unset($this->source[$key]);

        // Done, return!
        return $entry;
    }


    /**
     * Returns an IteratorInterface with array values containing only the specified columns
     *
     * @note This only works on sources that contain array / DataEntry object values. Any other value will cause an
     *       OutOfBoundsException
     *
     * @note If no columns were specified, then all columns will be assumed and the complete source will be returned
     *
     * @param array|string|null $columns
     *
     * @return IteratorInterface
     */
    public function getAllRowsMultipleColumns(array|string|null $columns): IteratorInterface
    {
        if (!$columns) {
            // Return all columns
            return new Iterator($this->source);
        }

        // Already ensure columns is an array here to avoid Arrays::keep() having to convert all the time, just in case.
        $return  = [];
        $columns = Arrays::force($columns);

        foreach ($this->source as $key => $value) {
            $value        = $this->ensureSourceValueHasColumns($value, $columns);
            $return[$key] = Arrays::keepKeysOrdered($value, $columns);
        }

        return new Iterator($return);
    }


    /**
     * Extracts headers from the specified columns
     *
     * @param array|string|null $columns
     *
     * @return array|null
     */
    protected function prepareHeaders(array|string|null $columns): ?array
    {
        if ($columns and is_array($columns)) {
            foreach ($columns as &$column) {
                $column = Strings::capitalize($column);
            }

            unset($column);
            return $columns;
        }

        return null;
    }


    /**
     * Extracts headers from the specified columns
     *
     * @param array|string|null $columns
     *
     * @return array|null
     */
    protected function prepareColumns(array|string|null $columns): ?array
    {
        $columns = $columns ?? $this->columns;

        if ($columns) {
            if (is_array($columns)) {
                return array_keys($columns);
            }

            return explode(',', $columns);
        }

        return null;
    }


    /**
     * Creates and returns an HTML table for the data in this list
     *
     * @param array|string|null $columns
     *
     * @return HtmlTableInterface
     */
    public function getHtmlTableObject(array|string|null $columns = null): HtmlTableInterface
    {
        $this->ensureArrays();

        $columns = $columns ?? $this->columns;

        return HtmlTable::new()
                        ->setId(strtolower(Strings::fromReverse(static::class, '\\')))
                        ->setHeaders($this->prepareHeaders($columns))
                        ->setSource($this->getAllRowsMultipleColumns($this->prepareColumns($columns)))
                        ->setRowCallbacks($this->row_callbacks)
                        ->setCheckboxSelectors(EnumTableIdColumn::checkbox);
    }


    /**
     * Creates and returns a fancy HTML data table for the data in this list
     *
     * @param array|string|null $columns
     *
     * @return HtmlDataTableInterface
     */
    public function getHtmlDataTableObject(array|string|null $columns = null): HtmlDataTableInterface
    {
        $this->ensureArrays();

        $columns = $columns ?? $this->columns;

        return HtmlDataTable::new()
                            ->setId(strtolower(Strings::fromReverse(static::class, '\\')))
                            ->setHeaders($this->prepareHeaders($columns))
                            ->setSource($this->getAllRowsMultipleColumns($this->prepareColumns($columns)))
                            ->setRowCallbacks($this->row_callbacks)
                            ->setCheckboxSelectors(EnumTableIdColumn::checkbox);
    }


    /**
     * Returns a SpreadSheet object with this object's source data in it
     *
     * @return SpreadSheetInterface
     */
    public function getSpreadSheet(): SpreadSheetInterface
    {
        return new SpreadSheet($this);
    }


    /**
     * Returns an HTML <select> for the entries in this list
     *
     * @return InputSelectInterface
     */
    public function getHtmlSelect(): InputSelectInterface
    {
        return $this->input_select_class::new()->setSource($this->source);
    }


    /**
     * Executes the specified callback function on each
     *
     * @param callable $callback
     *
     * @return static
     */
    public function eachField(callable $callback): static
    {
        foreach ($this->source as $key => &$value) {
            $callback($value, $key);
        }

        unset($value);

        return $this;
    }


    /**
     * Returns a diff between this Iterator and the specified Iterator or array
     *
     * @param IteratorInterface|array $source
     *
     * @return IteratorInterface
     */
    public function diff(IteratorInterface|array $source): IteratorInterface
    {
        if ($source instanceof IteratorInterface) {
            $source = $source->getSource();
        }

        return new Iterator(array_diff($this->source, $source));
    }


    /**
     * Removes all empty values from this Iterator object
     *
     * @return static
     */
    public function removeEmptyValues(): static
    {
        $this->source = Arrays::removeEmptyValues($this->source);

        return $this;
    }


    /**
     * Removes duplicate values from this Iterator
     *
     * @param int|null $flags Sorting type flags:
     *                        SORT_REGULAR - compare items normally (don't change types)
     *                        SORT_NUMERIC - compare items numerically
     *                        SORT_STRING - compare items as strings
     *                        SORT_LOCALE_STRING - compare items as strings, based on the current locale
     *
     * @return static
     */
    public function unique(int $flags = null): static
    {
        $this->source = array_unique($this->source, $flags);

        return $this;
    }


    /**
     * Will try to fetch the value key from the value itself
     *
     * By default this will return null, this way it will allow different iterator objects to fetch keys, or not at all
     *
     * @param mixed $value
     *
     * @return mixed
     */
    protected function fetchKeyFromValue(mixed $value): mixed
    {
        return null;
    }


    /**
     * Ensures that all iterator entries are arrays
     *
     * @return $this
     */
    public function ensureObjects(): static
    {
        foreach ($this->source as $key => &$value) {
            $value = $this->ensureObject($key);
        }

        unset($value);
        return $this;
    }


    /**
     * Ensure the entry we're going to return is from DataEntryInterface interface
     *
     * @param string|float|int $key
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] protected function ensureObject(string|float|int $key): mixed
    {
        if (is_object($this->source[$key])) {
            // Already object, assume it's the right type
            return $this->source[$key];
        }

        if (!is_a($this->getAcceptedDataType(), ArraySourceInterface::class, true)) {
            // Can only do this for objects that have ArraySourceInterface so that we can dump array sources in them.
            return $this->source[$key];
        }

        if (!is_array($this->source[$key])) {
            // Can only do this with arrays!
            return $this->source[$key];
        }

        $this->source[$key] = $this->getAcceptedDataType()::new()->setSource($this->source[$key]);

        return $this->source[$key];
    }


    /**
     * Ensures that all iterator entries are arrays
     *
     * @return $this
     */
    public function ensureArrays(): static
    {
        foreach ($this->source as $key => &$value) {
            $value = $this->ensureArray($key);
        }

        unset($value);
        return $this;
    }


    /**
     * Ensure the entry we're going to return is from DataEntryInterface interface
     *
     * @param string|float|int $key
     *
     * @return mixed
     *
     * @throws OutOfBoundsException
     */
    #[ReturnTypeWillChange] protected function ensureArray(string|float|int $key): array
    {
        if (is_array($this->source[$key])) {
            // Already object, assume it's the right type
            return $this->source[$key];
        }

        if (is_scalar($this->source[$key])) {
            return [$this->source[$key]];
        }

        if (is_a($this->source[$key], ArraySourceInterface::class, true)) {
            // Can only do this for objects that have ArraySourceInterface so that we can dump array sources in them.
            return $this->source[$key]->__toArray();
        }

        throw new OutOfBoundsException(tr('Cannot convert source key ":key" to array, the value ":value" cannot be converted', [
            ':key'   => $key,
            ':value' => $this->source[$key]
        ]));
    }
}
