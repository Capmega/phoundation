<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Interfaces;

use Phoundation\Data\Validator\Interfaces\InterfaceDataValidator;
use Phoundation\Web\Http\Html\Enums\InputType;
use Phoundation\Web\Http\Html\Interfaces\InputElementInterface;


/**
 * Interface DataEntryFieldDefinition
 *
 * Contains the definitions for a single DataEntry object field
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
Interface DataEntryFieldDefinition
{
    /**
     * DataEntryFieldDefinition class constructor
     *
     * @param string|null $field
     */
    public function __construct(?string $field = null);

    /**
     * Returns a new static object
     *
     * @param string|null $field
     * @return static
     */
    public static function new(?string $field = null): static;

    /**
     * Returns the field
     *
     * @return string|null
     */
    public function getField(): ?string;

    /**
     * Sets the field
     *
     * @param string|null $field
     * @return static
     */
    public function setField(?string $field): static;

    /**
     * Returns the internal definitions for this field
     *
     * @return array
     */
    function getDefinitions(): array;


    /**
     * Sets all the internal definitions for this field in one go
     *
     * @param array $definitions
     * @return static
     */
    public function setDefinitions(array $definitions): static;

    /**
     * Add specified value for the specified key for this DataEntry field
     *
     * @param string $key
     * @return callable|string|float|int|bool|null
     */
    public function getKey(string $key): callable|string|float|int|bool|null;

    /**
     * Add specified value for the specified key for this DataEntry field
     *
     * @param string $key
     * @param string|float|int|bool|null $value
     * @return $this
     */
    function setKey(string $key, callable|string|float|int|bool|null $value): static;

    /**
     * Returns if this field is visible in HTML clients
     *
     * If false, the field will not be displayed and typically will be modified through a virtual field instead.
     *
     * @note Defaults to true
     * @return bool|null
     * @see DataEntryFieldDefinition::getVirtual()
     */
    public function getVisible(): ?bool;

    /**
     * Sets if this field is visible in HTML clients
     *
     * If false, the field will not be displayed and typically will be modified through a virtual field instead.
     *
     * @note Defaults to true
     * @param bool|null $value
     * @return $this
     *@see DataEntryFieldDefinition::setVirtual()
     */
    function setVisible(?bool $value): static;

    /**
     * Returns if this field is virtual
     *
     * If this field is virtual, it will be visible and can be manipulated but will have no direct database entry.
     * Instead, it will modify a different field. This is (for example) used in an entry that uses countries_id which
     * will be invisible whilst the virtual field "country" will modify countries_id
     *
     * @note Defaults to false
     * @return bool
     */
    public function getMeta(): bool;

    /**
     * Sets if this field is virtual
     *
     * If this field is virtual, it will be visible and can be manipulated but will have no direct database entry.
     * Instead, it will modify a different field. This is (for example) used in an entry that uses countries_id which
     * will be invisible whilst the virtual field "country" will modify countries_id
     *
     * @note Defaults to false
     * @param bool $value
     * @return $this
     */
    function setMeta(bool $value): static;

    /**
     * Returns if this field is virtual
     *
     * If this field is virtual, it will be visible and can be manipulated but will have no direct database entry.
     * Instead, it will modify a different field. This is (for example) used in an entry that uses countries_id which
     * will be invisible whilst the virtual field "country" will modify countries_id
     *
     * @note Defaults to false
     * @see DataEntryFieldDefinition::getVisible()
     * @return bool|null
     */
    public function getVirtual(): ?bool;

    /**
     * Sets if this field is virtual
     *
     * If this field is virtual, it will be visible and can be manipulated but will have no direct database entry.
     * Instead, it will modify a different field. This is (for example) used in an entry that uses countries_id which
     * will be invisible whilst the virtual field "country" will modify countries_id
     *
     * @see DataEntryFieldDefinition::setVisible()
     * @note Defaults to false
     * @param bool|null $value
     * @return $this
     */
    function setVirtual(?bool $value): static;

    /**
     * Returns the HTML client element to be used for this field
     *
     * @return string|null
     */
    public function getElement(): string|null;

    /**
     * SEts the HTML client element to be used for this field
     *
     * @param InputElementInterface|null $value
     * @return $this
     */
    function setElement(InputElementInterface|null $value): static;

    /**
     * Returns the HTML client element to be used for this field
     *
     * @return callable|string|null
     */
    public function getContent(): callable|string|null;

    /**
     * Sets the HTML client element to be used for this field
     *
     * @param callable|string|null $value
     * @return static
     */
    public function setContent(callable|string|null $value): static;

    /**
     * Return the type of input element.
     *
     * @return string|null
     */
    public function getType(): ?string;

    /**
     * Sets the type of input element.
     *
     * @param InputType|null $value
     * @return $this
     */
    function setInputType(?InputType $value): static;


    /**
     * Returns if the value cannot be modified and this element will be shown as disabled on HTML clients
     *
     * @note Defaults to false
     * @return bool|null
     */
    public function getReadonly(): ?bool;

    /**
     * If true, the value cannot be modified and this element will be shown as disabled on HTML clients
     *
     * @note Defaults to false
     * @param bool|null $value
     * @return $this
     */
    function setReadonly(?bool $value): static;

    /**
     * Returns if the value cannot be modified and this element will be shown as disabled on HTML clients
     *
     * @note Defaults to false
     * @return bool|null
     */
    public function getDisabled(): ?bool;

    /**
     * If true, the value cannot be modified and this element will be shown as disabled on HTML clients
     *
     * @note Defaults to false
     * @param bool|null $value
     * @return $this
     */
    function setDisabled(?bool $value): static;

    /**
     * The label to be shown on HTML clients
     *
     * @return string|null $value
     */
    public function getLabel(): ?string;

    /**
     * The label to be shown on HTML clients
     *
     * @param string|null $value
     * @return $this
     */
    function setLabel(?string $value): static;

    /**
     * Returns the boilerplate col size for this field, must be integer number between 1 and 12
     *
     * @return int|null
     */
    public function getSize(): ?int;

    /**
     * Sets the boilerplate col size for this field, must be integer number between 1 and 12
     *
     * @param int|null $value
     * @return $this
     */
    function setSize(?int $value): static;

    /**
     * Returns a data source for the HTML client element contents of this field
     *
     * The data source may be specified as a query string or a key => value array
     *
     * @return array|string|null
     */
    public function getSource(): array|string|null;

    /**
     * Sets a data source for the HTML client element contents of this field
     *
     * The data source may be specified as a query string or a key => value array
     *
     * @param array|string|null $value
     * @return $this
     */
    function setSource(array|string|null $value): static;

    /**
     * Returns a query execute bound variables execute array for the specified query string source
     *
     * @note Requires "source" to be a query string
     * @return array|null
     */
    public function getExecute(): ?array;

    /**
     * Sets a query execute bound variables execute array for the specified query string source
     *
     * @note Requires "source" to be a query string
     * @param array|null $value
     * @return $this
     */
    function setExecute(?array $value): static;

    /**
     * Returns the cli auto-completion queries for this field
     *
     * @return array|bool|null
     */
    public function getAutoComplete(): array|bool|null;

    /**
     * Sets the cli auto-completion queries for this field
     *
     * @param array|bool|null $value
     * @return $this
     */
    function setAutoComplete(array|bool|null $value): static;

    /**
     * Returns the alternative CLI field names for this field
     *
     * @return string|null
     */
    public function getCliField(): ?string;

    /**
     * Sets the alternative CLI field names for this field
     *
     * @param string|null $value
     * @return $this
     */
    function setCliField(?string $value): static;

    /**
     * Returns if this field is optional or not
     *
     * @note Defaults to false
     * @return bool|null
     */
    public function getOptional(): ?bool;

    /**
     * Sets if this field is optional or not
     *
     * @note Defaults to false
     * @param bool $value
     * @param string|float|int|bool|null $default
     * @return $this
     */
    function setOptional(?bool $value, string|float|int|bool|null $default = null): static;

    /**
     * Returns the placeholder for this field
     *
     * @return string|null
     */
    public function getPlaceholder(): ?string;

    /**
     * Sets the placeholder for this field
     *
     * @param string|null $value
     * @return $this
     */
    function setPlaceholder(?string $value): static;

    /**
     * Returns the minlength for this textarea or text input field
     *
     * @return int|null
     */
    public function getMinlength(): ?int;

    /**
     * Returns the maxlength for this textarea or text ibput field
     *
     * @return int|null
     */
    public function getMaxlength(): ?int;

    /**
     * Sets the maxlength for this textarea or text input field
     *
     * @param int|null $value
     * @return $this
     */
    function setMaxlength(?int $value): static;

    /**
     * Returns the pattern for this textarea or text input field
     *
     * @return string|null
     */
    public function getPattern(): ?string;

    /**
     * Sets the pattern for this textarea or text input field
     *
     * @param string|null $value
     * @return $this
     */
    function setPattern(?string $value): static;

    /**
     * Returns the minimum value for number input elements
     *
     * @return float|int|null
     */
    public function getMin(): float|int|null;

    /**
     * Set the minimum value for number input elements
     *
     * @param float|int|null $value
     * @return $this
     */
    function setMin(float|int|null $value): static;

    /**
     * Returns the maximum value for number input elements
     *
     * @return float|int|null
     */
    public function getMax(): float|int|null;

    /**
     * Set the maximum value for number input elements
     *
     * @param float|int|null $value
     * @return $this
     */
    function setMax(float|int|null $value): static;

    /**
     * Return the step value for number input elements
     *
     * @return string|float|int|null
     */
    public function getStep(): string|float|int|null;

    /**
     * Set the step value for number input elements
     *
     * @param string|float|int|null $value
     * @return $this
     */
    function setStep(string|float|int|null $value): static;

    /**
     * Returns the rows value for textarea elements
     *
     * @return int|null
     */
    public function getRows(): int|null;

    /**
     * Sets the rows value for textarea elements
     *
     * @param int|null $value
     * @return static
     */
    public function setRows(int|null $value): static;

    /**
     * Returns the default value for this field for display
     *
     * @return string|float|int|bool|null
     */
    public function getDefault(): string|float|int|bool|null;

    /**
     * Sets the default value for this field for display
     *
     * @param string|float|int|bool|null $value
     * @return $this
     */
    function setDefault(string|float|int|bool|null $value): static;

    /**
     * Returns the default value for this field in the database
     *
     * @return string|float|int|null
     */
    public function getDefaultDb(): string|float|int|null;

    /**
     * Sets the default value for this field in the database
     *
     * @param string|float|int|null $value
     * @return $this
     */
    function setDefaultDb(string|float|int|null $value): static;

    /**
     * Returns if this field should be stored with NULL in the database if empty
     *
     * @note Defaults to false
     * @return bool
     */
    public function getNullDb(): bool;

    /**
     * Sets if this field should be stored with NULL in the database if empty
     *
     * @note Defaults to false
     * @param bool $value
     * @param string|float|int|null $default
     * @return static
     */
    public function setNullDb(bool $value, string|float|int|null $default = null): static;

    /**
     * Returns if this field should be disabled if the value is NULL
     *
     * @note Defaults to false
     * @return bool
     */
    public function getNullDisabled(): bool;

    /**
     * Sets if this field should be disabled if the value is NULL
     *
     * @note Defaults to false
     * @param bool|null $value
     * @return $this
     */
    function setNullDisabled(?bool $value): static;

    /**
     * Returns if this field should be readonly if the value is NULL
     *
     * @note Defaults to false
     * @return bool
     */
    public function getNullReadonly(): bool;

    /**
     * Sets if this field should be readonly if the value is NULL
     *
     * @note Defaults to false
     * @param bool|null $value
     * @return $this
     */
    function setNullReadonly(?bool $value): static;

    /**
     * Returns the type for this element if the value is NULL
     *
     * @return string|null
     */
    public function getNullType(): ?string;

    /**
     * Sets the type for this element if the value is NULL
     *
     * @param InputType|null $value
     * @return $this
     */
    function setNullInputType(?InputType $value): static;

    /**
     * Returns true if the specified input type is supported
     *
     * @param string $type
     * @return bool
     */
    function inputTypeSupported(string $type): bool;

    /**
     * Validate this field according to the field definitions
     *
     * @param InterfaceDataValidator $validator
     * @return void
     */
    public function validate(InterfaceDataValidator $validator): void;
}