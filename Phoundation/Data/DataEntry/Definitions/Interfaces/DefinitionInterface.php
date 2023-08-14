<?php

namespace Phoundation\Data\DataEntry\Definitions\Interfaces;


use PDOStatement;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Web\Http\Html\Components\Interfaces\InputElementInterface;
use Phoundation\Web\Http\Html\Components\Interfaces\InputTypeExtendedInterface;
use Phoundation\Web\Http\Html\Components\Interfaces\InputTypeInterface;
use Phoundation\Web\Http\Html\Enums\InputType;
use Stringable;

/**
 * Class Definition
 *
 * Contains the definitions for a single DataEntry object field
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
interface DefinitionInterface
{
    /**
     * UsesNewField class constructor
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null $field
     */
    public function __construct(?DataEntryInterface $data_entry, ?string $field = null);


    /**
     * Returns a new static object
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null $field
     * @return DefinitionInterface
     */
    public static function new(?DataEntryInterface $data_entry, ?string $field = null): DefinitionInterface;

    /**
     * Returns the query builder
     *
     * @return QueryBuilderInterface
     */
    public function getQueryBuilder(): QueryBuilderInterface;

    /**
     * Modify the contents of the query builder through a callback function
     *
     * @param callable $callback
     * @return $this
     */
    public function modifyQueryBuilder(callable $callback): static;

    /**
     * Returns the static value for this field
     *
     * @return callable|string|float|int|bool|null
     */
    public function getValue(): callable|string|float|int|bool|null;

    /**
     * Sets static value for this field
     *
     * @param callable|string|float|int|bool|null $value
     * @param bool $only_when_new = false
     * @return static
     */
    public function setValue(callable|string|float|int|bool|null $value, bool $only_when_new = false): static;

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
     * @return DefinitionInterface
     */
    public function setField(?string $field): DefinitionInterface;

    /**
     * Returns the internal definitions for this field
     *
     * @return array
     */
    public function getRules(): array;

    /**
     * Sets all the internal rules for this field in one go
     *
     * @param array $rules
     * @return static
     */
    public function setRules(array $rules): static;

    /**
     * Add specified value for the specified key for this DataEntry field
     *
     * @param string $key
     * @return mixed
     */
    public function getKey(string $key): mixed;

    /**
     * Add specified value for the specified key for this DataEntry field
     *
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function setKey(string $key, mixed $value): static;

    /**
     * Returns if this field will not set the DataEntry to "modified" state when changed
     *
     * @note Defaults to true
     * @return bool|null
     */
    public function getIgnoreModify(): ?bool;

    /**
     * Sets if this field will not set the DataEntry to "modified" state when changed
     *
     * @note Defaults to false
     * @param bool|null $value
     * @return static
     */
    public function setIgnoreModify(?bool $value): static;

    /**
     * Returns if this field is visible in HTML clients
     *
     * If false, the field will not be displayed and typically will be modified through a virtual field instead.
     *
     * @note Defaults to true
     * @return bool|null
     * @see Definition::getVirtual()
     */
    public function getVisible(): ?bool;

    /**
     * Sets if this field is visible in HTML clients
     *
     * If false, the field will not be displayed and typically will be modified through a virtual field instead.
     *
     * @note Defaults to true
     * @param bool|null $value
     * @return static
     * @see Definition::setVirtual()
     */
    public function setVisible(?bool $value): static;

    /**
     * Return if this field is a meta field
     *
     * If this field is a meta field, it will be readonly for user actions
     *
     * @note Defaults to false
     * @return bool
     * @see Definition::getVisible()
     */
    public function getMeta(): bool;

    /**
     * Returns if this field is virtual
     *
     * If this field is virtual, it will be visible and can be manipulated but will have no direct database entry.
     * Instead, it will modify a different field. This is (for example) used in an entry that uses countries_id which
     * will be invisible whilst the virtual field "country" will modify countries_id
     *
     * @note Defaults to false
     * @return bool|null
     * @see Definition::getVisible()
     */
    public function getVirtual(): ?bool;

    /**
     * Sets if this field is virtual
     *
     * If this field is virtual, it will be visible and can be manipulated but will have no direct database entry.
     * Instead, it will modify a different field. This is (for example) used in an entry that uses countries_id which
     * will be invisible whilst the virtual field "country" will modify countries_id
     *
     * @note Defaults to false
     * @param bool|null $value
     * @return static
     * @see Definition::setVisible()
     */
    public function setVirtual(?bool $value): static;

    /**
     * Returns the HTML client element to be used for this field
     *
     * @return string|null
     */
    public function getElement(): string|null;

    /**
     * Sets the HTML client element to be used for this field
     *
     * @param InputElementInterface|null $value
     * @return static
     */
    public function setElement(InputElementInterface|null $value): static;

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
     * @param bool $make_safe
     * @return static
     */
    public function setContent(callable|string|null $value, bool $make_safe = false): static;

    /**
     * Return the type of input element.
     *
     * @return string|null
     */
    public function getType(): ?string;

    /**
     * Sets the type of input element.
     *
     * @param InputTypeInterface|InputTypeExtendedInterface|null $value
     * @return static
     */
    public function setInputType(InputTypeInterface|InputTypeExtendedInterface|null $value): static;

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
     * @return static
     */
    public function setReadonly(?bool $value): static;

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
     * @return static
     */
    public function setDisabled(?bool $value): static;

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
     * @return static
     */
    public function setLabel(?string $value): static;

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
     * @return static
     */
    public function setSize(?int $value): static;

    /**
     * Returns a data source for the HTML client element contents of this field
     *
     * The data source may be specified as a query string or a key => value array
     *
     * @return array|PDOStatement|Stringable|null
     */
    public function getSource(): array|PDOStatement|Stringable|null;

    /**
     * Sets a data source for the HTML client element contents of this field
     *
     * The data source may be specified as a query string or a key => value array
     *
     * @param array|PDOStatement|Stringable|null $value
     * @return static
     */
    public function setSource(array|PDOStatement|Stringable|null $value): static;

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
     * @param array|string|null $value
     * @return static
     */
    public function setExecute(array|string|null $value): static;

    /**
     * If true, will enable browser auto suggest for this input control
     *
     * @note Defaults to false
     * @return bool
     */
    public function getAutoComplete(): bool;

    /**
     * If true, will enable browser auto suggest for this input control
     *
     * @note Defaults to false
     * @param bool|null $value
     * @return static
     */
    public function setAutoComplete(?bool $value): static;

    /**
     * Returns the cli auto-completion queries for this field
     *
     * @return array|bool|null
     */
    public function getCliAutoComplete(): array|bool|null;

    /**
     * Sets the cli auto-completion queries for this field
     *
     * @param array|bool|null $value
     * @return static
     */
    public function setCliAutoComplete(array|bool|null $value): static;

    /**
     * Returns the alternative CLI field names for this field
     *
     * @param ValidatorInterface|null $validator
     * @return string|null
     */
    public function getCliField(?ValidatorInterface $validator = null): ?string;

    /**
     * Sets the alternative CLI field names for this field
     *
     * @param string|null $value
     * @return static
     */
    public function setCliField(?string $value): static;

    /**
     * Returns if this field is optional or not
     *
     * @note Defaults to false
     * @return bool
     */
    public function getOptional(): bool;

    /**
     * Sets if this field is optional or not
     *
     * @note Defaults to false
     * @param bool|null $value
     * @param mixed $default
     * @return static
     */
    public function setOptional(?bool $value, mixed $default = null): static;

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
     * @return static
     */
    public function setPlaceholder(?string $value): static;

    /**
     * Returns the minlength for this textarea or text input field
     *
     * @return int|null
     */
    public function getMinlength(): ?int;

    /**
     * Sets the minlength for this textarea or text input field
     *
     * @param int|null $value
     * @return static
     */
    public function setMinlength(?int $value): static;

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
     * @return static
     */
    public function setMaxlength(?int $value): static;

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
     * @return static
     */
    public function setPattern(?string $value): static;

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
     * @return static
     */
    public function setMin(float|int|null $value): static;

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
     * @return static
     */
    public function setMax(float|int|null $value): static;

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
     * @return static
     */
    public function setStep(string|float|int|null $value): static;

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
    public function setRows(?int $value): static;

    /**
     * Returns the default value for this field
     *
     * @return mixed
     */
    public function getDefault(): mixed;

    /**
     * Sets the default value for this field
     *
     * @param mixed $value
     * @return static
     */
    public function setDefault(mixed $value): static;

    /**
     * Returns the initial default value for this field
     *
     * @return mixed
     */
    public function getInitialDefault(): mixed;

    /**
     * Sets the initial default value for this field
     *
     * @param mixed $value
     * @return static
     */
    public function setInitialDefault(mixed $value): static;

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
     * @return static
     */
    public function setNullDisabled(?bool $value): static;

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
     * @return static
     */
    public function setNullReadonly(?bool $value): static;

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
     * @return static
     */
    public function setNullInputType(?InputType $value): static;

    /**
     * Returns the type for this element if the value is NULL
     *
     * @return array|null
     */
    public function getValidationFunctions(): ?array;

    /**
     * Clears all currently existing validation functions for this definition
     *
     * @return static
     */
    public function clearValidationFunctions(): static;

    /**
     * Adds the specified validation function to the validation functions list for this definition
     *
     * @param callable $function
     * @return static
     */
    public function addValidationFunction(callable $function): static;

    /**
     * Returns the help text for this field
     *
     * @return string|null
     */
    public function getHelpText(): ?string;

    /**
     * Sets the help text for this field
     *
     * @param string|null $value
     * @return static
     */
    public function setHelpText(?string $value): static;

    /**
     * Returns the help text group for this field
     *
     * @return string|null
     */
    public function getHelpGroup(): ?string;

    /**
     * Sets the help text group for this field
     *
     * @param string|null $value
     * @return static
     */
    public function setHelpGroup(?string $value): static;

    /**
     * Returns true if the specified input type is supported
     *
     * @param string $type
     * @return bool
     */
    public function inputTypeSupported(string $type): bool;

    /**
     * Validate this field according to the field definitions
     *
     * @param ValidatorInterface $validator
     * @param string|null $prefix
     * @return bool
     */
    public function validate(ValidatorInterface $validator, ?string $prefix): bool;

    /**
     * Returns variables for the component
     *
     * Format should be like
     *
     * [
     *     'countries_id' => '$("#countries_id").val()',
     *     'states_id'    => '$("#states_id").val()'
     * ]
     *
     * @return array|null
     */
    public function getVariables(): array|null;

    /**
     * Sets variables for the component
     *
     * Format should be like
     *
     * [
     *     'countries_id' => '$("#countries_id").val()',
     *     'states_id'    => '$("#states_id").val()'
     * ]
     *
     * @param array|null $value
     * @return static
     */
    public function setVariables(array|null $value): static;

    /**
     * Returns the display_callback for this field
     *
     * @return callable|null
     */
    public function getDisplayCallback(): ?callable;

    /**
     * Sets the display_callback for this field
     *
     * @param callable|null $value
     * @return static
     */
    public function setDisplayCallback(?callable $value): static;
}