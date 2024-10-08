<?php
/**
 * Class Definition
 *
 * Contains the definitions for a single DataEntry object column
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Definitions\Interfaces;

use PDOStatement;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonInterface;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonsInterface;
use Phoundation\Web\Html\Components\Input\Interfaces\RenderInterface;
use Phoundation\Web\Html\Components\Interfaces\ScriptInterface;
use Phoundation\Web\Html\Enums\EnumInputType;
use Phoundation\Web\Html\Enums\EnumElement;
use Stringable;

interface DefinitionInterface
{
    /**
     * Sets if this column should ignore validation
     *
     * @param bool $no_validation
     *
     * @return static
     */
    public function setNoValidation(bool $no_validation): static;

    /**
     * Returns the query builder from the data entry
     *
     * @return QueryBuilderInterface
     */
    public function getQueryBuilder(): QueryBuilderInterface;


    /**
     * Modify the contents of the query builder through a callback function
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function modifyQueryBuilder(callable $callback): static;


    /**
     * Returns the internal definitions for this column
     *
     * @return array
     */
    public function getSource(): array;


    /**
     * Sets all the internal definitions for this column in one go
     *
     * @param array $source
     *
     * @return static
     */
    public function setSource(array $source): static;


    /**
     * Returns the prefix automatically added to this value, after validation
     *
     * @return string|null
     */
    public function getPrefix(): ?string;


    /**
     * Sets the prefix automatically added to this value, after validation
     *
     * @param string|null $prefix
     *
     * @return static
     */
    public function setPrefix(?string $prefix): static;


    /**
     * Returns the postfix automatically added to this value, after validation
     *
     * @return string|null
     */
    public function getPostfix(): ?string;


    /**
     * Sets the postfix automatically added to this value, after validation
     *
     * @param string|null $postfix
     *
     * @return static
     */
    public function setPostfix(?string $postfix): static;


    /**
     * Add specified value for the specified key for this DataEntry column
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getKey(string $key): mixed;


    /**
     * Add specified value for the specified key for this DataEntry column
     *
     * @param mixed  $value
     * @param string $key
     * @param bool   $trim
     *
     * @return static
     */
    public function setKey(mixed $value, string $key, bool $trim): static;


    /**
     * Returns if this column is rendered as HTML or not
     *
     * If false, the column will not be rendered and sent to the client, and typically will be modified through a
     * virtual column instead.
     *
     * @note Defaults to true
     * @return bool|null
     * @see  Definition::getVirtual()
     */
    public function getRender(): ?bool;


    /**
     * Sets if this column is rendered as HTML or not
     *
     * If false, the column will not be rendered and sent to the client, and typically will be modified through a
     * virtual column instead.
     *
     * @note Defaults to true
     *
     * @param bool|null $value
     *
     * @return static
     * @see  Definition::setVirtual()
     */
    public function setRender(?bool $value): static;


    /**
     * Returns if this column is visible in HTML clients
     *
     * If false, the column will have the "invisible" class added
     *
     * @note Defaults to true
     * @return bool|null
     * @see  Definition::getVirtual()
     */
    public function getVisible(): ?bool;


    /**
     * Sets if this column is visible in HTML clients
     *
     * If false, the column will have the "invisible" class added
     *
     * @note Defaults to true
     *
     * @param bool|null $value
     *
     * @return static
     * @see  Definition::setVirtual()
     */
    public function setVisible(?bool $value): static;


    /**
     * Returns if this column is displayed in HTML clients
     *
     * If false, the column will have the "d-none" class added
     *
     * @note Defaults to true
     * @return bool|null
     * @see  Definition::getVirtual()
     */
    public function getDisplay(): ?bool;


    /**
     * Sets if this column is displayed in HTML clients
     *
     * If false, the column will have the "d-none" class added
     *
     * @note Defaults to true
     *
     * @param bool|null $value
     *
     * @return static
     * @see  Definition::setVirtual()
     */
    public function setDisplay(?bool $value): static;


    /**
     * Returns the extra HTML classes for this DataEntryForm object
     *
     * @param bool $add_prefixless_names
     *
     * @return array
     * @see Definition::getVirtual()
     */
    public function getClasses(bool $add_prefixless_names = true): array;


    /**
     * Adds the specified HTML classes to the DataEntryForm object
     *
     * @note When specifying multiple classes in a string, make sure they are space separated!
     *
     * @param array|string $value
     *
     * @return static
     * @see  Definition::setVirtual()
     */
    public function addClasses(array|string $value): static;


    /**
     * Sets specified HTML classes to the DataEntryForm object
     *
     * @note When specifying multiple classes in a string, make sure they are space separated!
     *
     * @param array|string $value
     *
     * @return static
     * @see  Definition::setVirtual()
     */
    public function setClasses(array|string $value): static;


    /**
     * Returns the extra HTML data for this DataEntryForm object
     *
     * @return array
     */
    public function getData(): array;


    /**
     * Adds the specified HTML data to the DataEntryForm object
     *
     * @note When specifying multiple data in a string, make sure they are space separated!
     *
     * @param array|string $value
     * @param string       $key
     *
     * @return static
     * @see  Definition::setVirtual()
     */
    public function addData(array|string $value, string $key): static;


    /**
     * Sets specified HTML data to the DataEntryForm object
     *
     * @note When specifying multiple data in a string, make sure they are space separated!
     *
     * @param IteratorInterface|array $value
     *
     * @return static
     * @see  Definition::setVirtual()
     */
    public function setData(IteratorInterface|array $value): static;


    /**
     * Returns the extra HTML data for this DataEntryForm object
     *
     * @return array
     */
    public function getScripts(): array;


    /**
     * @param ScriptInterface $script
     *
     * @return $this
     */
    public function addScript(ScriptInterface $script): static;


    /**
     * Returns the extra HTML aria for this AriaEntryForm object
     *
     * @return array
     */
    public function getAria(): array;


    /**
     * Adds the specified HTML aria to the AriaEntryForm object
     *
     * @note When specifying multiple aria in a string, make sure they are space separated!
     *
     * @param array|string $value
     * @param string       $key
     *
     * @return static
     * @see  Definition::setVirtual()
     */
    public function addAria(array|string $value, string $key): static;


    /**
     * Sets specified HTML aria to the AriaEntryForm object
     *
     * @note When specifying multiple aria in a string, make sure they are space separated!
     *
     * @param IteratorInterface|array $value
     *
     * @return static
     * @see  Definition::setVirtual()
     */
    public function setAria(IteratorInterface|array $value): static;


    /**
     * Returns if this column will not set the DataEntry to "modified" state when changed
     *
     * @note Defaults to true
     * @return bool|null
     */
    public function getIgnoreModify(): ?bool;


    /**
     * Sets if this column will not set the DataEntry to "modified" state when changed
     *
     * @note Defaults to false
     *
     * @param bool|null $value
     *
     * @return static
     */
    public function setIgnoreModify(?bool $value): static;


    /**
     * Return if this column is a meta column
     *
     * If this column is a meta column, it will be readonly for user actions
     *
     * @note Defaults to false
     * @return bool
     * @see  Definition::getRender()
     */
    public function isMeta(): bool;


    /**
     * Returns if this column is virtual
     *
     * If this column is virtual, it will be visible and can be manipulated but will have no direct database entry.
     * Instead, it will modify a different column. This is (for example) used in an entry that uses countries_id which
     * will be invisible whilst the virtual column "country" will modify countries_id
     *
     * @note Defaults to false
     * @return bool|null
     * @see  Definition::getRender()
     */
    public function getVirtual(): ?bool;


    /**
     * Sets if this column is virtual
     *
     * If this column is virtual, it will be visible and can be manipulated but will have no direct database entry.
     * Instead, it will modify a different column. This is (for example) used in an entry that uses countries_id which
     * will be invisible whilst the virtual column "country" will modify countries_id
     *
     * @note Defaults to false
     *
     * @param bool|null $value
     *
     * @return static
     * @see  Definition::setRender()
     */
    public function setVirtual(?bool $value): static;


    /**
     * Returns if this column updates directly, bypassing DataEntry::setSourceValue()
     *
     * @note Defaults to false
     * @return bool|null
     * @see  Definition::getRender()
     */
    public function getDirectUpdate(): ?bool;


    /**
     * Sets if this column updates directly, bypassing DataEntry::setSourceValue()
     *
     * @note Defaults to false
     *
     * @param bool|null $value
     *
     * @return static
     * @see  Definition::setRender()
     */
    public function setDirectUpdate(?bool $value): static;


    /**
     * Returns the static value for this column
     *
     * @return callable|string|float|int|bool|null
     */
    public function getValue(): callable|string|float|int|bool|null;


    /**
     * Sets static value for this column
     *
     * @param RenderInterface|callable|string|float|int|bool|null $value
     * @param bool                                                $only_when_new = false
     *
     * @return static
     */
    public function setValue(RenderInterface|callable|string|float|int|bool|null $value, bool $only_when_new = false): static;


    /**
     * Returns the autofocus for this column
     *
     * @return bool
     */
    public function getAutoFocus(): bool;


    /**
     * Sets the autofocus for this column
     *
     * @param bool $auto_focus
     *
     * @return static
     */
    public function setAutoFocus(bool $auto_focus): static;


    /**
     * Returns the HTML client element to be used for this column
     *
     * @return string|null
     */
    public function getElement(): string|null;


    /**
     * Sets the HTML client element to be used for this column
     *
     * @param EnumElement|null $value
     *
     * @return static
     */
    public function setElement(EnumElement|null $value): static;


    /**
     * Returns the HTML component to be used for this column
     *
     * @return RenderInterface|callable|string|null
     */
    public function getContent(): RenderInterface|callable|string|null;


    /**
     * Sets the HTML component to be used for this column
     *
     * @param RenderInterface|callable|string|null $value
     * @param bool                                 $make_safe
     *
     * @return static
     */
    public function setContent(RenderInterface|callable|string|null $value, bool $make_safe = false): static;


    /**
     * Return the type of input element.
     *
     * @return EnumInputType
     */
    public function getInputType(): EnumInputType;


    /**
     * Sets the type of input element.
     *
     * @param EnumInputType|string $value
     *
     * @return static
     */
    public function setInputType(EnumInputType|string $value): static;


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
     *
     * @param bool|null $value
     *
     * @return static
     */
    public function setReadonly(?bool $value): static;


    /**
     * Returns if the entry is hidden (and will be rendered as a hidden element)
     *
     * @note Defaults to false
     * @return bool|null
     */
    public function getHidden(): ?bool;


    /**
     * Sets if the entry is hidden (and will be rendered as a hidden element)
     *
     * @note Defaults to false
     *
     * @param bool|null $value
     *
     * @return static
     */
    public function setHidden(?bool $value): static;


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
     *
     * @param bool|null $value
     *
     * @return static
     */
    public function setAutoComplete(?bool $value): static;


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
     *
     * @param bool|null $value
     *
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
     *
     * @return static
     */
    public function setLabel(?string $value): static;


    /**
     * Returns the boilerplate col size for this column, must be integer number between 1 and 12
     *
     * @return int|null
     */
    public function getSize(): ?int;


    /**
     * Sets the boilerplate col size for this column, must be integer number between 1 and 12
     *
     * @param int|null $value
     *
     * @return static
     */
    public function setSize(?int $value): static;


    /**
     * Returns a data source for the HTML client element contents of this column
     *
     * The data source may be specified as a query string or a key => value array
     *
     * @return array|PDOStatement|Stringable|string|null
     */
    public function getDataSource(): array|PDOStatement|Stringable|string|null;


    /**
     * Sets a data source for the HTML client element contents of this column
     *
     * The data source may be specified as a query string or a key => value array
     *
     * @param array|PDOStatement|Stringable|string|null $value
     *
     * @return static
     */
    public function setDataSource(array|PDOStatement|Stringable|string|null $value): static;


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
     *
     * @return static
     */
    public function setVariables(array|null $value): static;


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
     *
     * @param array|string|null $value
     *
     * @return static
     */
    public function setExecute(array|string|null $value): static;


    /**
     * Returns the cli auto-completion queries for this column
     *
     * @return array|bool|null
     */
    public function getCliAutoComplete(): array|bool|null;


    /**
     * Sets the cli auto-completion queries for this column
     *
     * @param array|bool|null $value
     *
     * @return static
     */
    public function setCliAutoComplete(array|bool|null $value): static;


    /**
     * Returns the alternative CLI column names for this column
     *
     * @return string|null
     */
    public function getCliColumn(): ?string;


    /**
     * Sets the alternative CLI column names for this column
     *
     * @param string|null $value
     *
     * @return static
     */
    public function setCliColumn(?string $value): static;


    /**
     * Returns if this column is optional or not
     *
     * @note Defaults to false
     * @return bool
     */
    public function getOptional(): bool;


    /**
     * Returns if this column is required or not
     *
     * @note Is the exact opposite of Definition::getOptional()
     * @note Defaults to true
     * @return bool
     */
    public function getRequired(): bool;


    /**
     * Sets if this column is optional or not
     *
     * @note Defaults to false
     *
     * @param bool|null $value
     * @param mixed     $initial_default
     *
     * @return static
     */
    public function setOptional(?bool $value, mixed $initial_default = null): static;


    /**
     * Returns the placeholder for this column
     *
     * @return string|null
     */
    public function getPlaceholder(): ?string;


    /**
     * Sets the placeholder for this column
     *
     * @param string|null $value
     *
     * @return static
     */
    public function setPlaceholder(?string $value): static;


    /**
     * Returns the display_callback for this column
     *
     * @return callable|null
     */
    public function getDisplayCallback(): ?callable;


    /**
     * Sets the display_callback for this column
     *
     * @param callable|null $value
     *
     * @return static
     */
    public function setDisplayCallback(?callable $value): static;


    /**
     * Returns the minlength for this textarea or text input column
     *
     * @return int|null
     */
    public function getMinlength(): ?int;


    /**
     * Sets the minlength for this textarea or text input column
     *
     * @param int|null $value
     *
     * @return static
     */
    public function setMinlength(?int $value): static;


    /**
     * Returns the maxlength for this textarea or text ibput column
     *
     * @return int|null
     */
    public function getMaxlength(): ?int;


    /**
     * Sets the maxlength for this textarea or text input column
     *
     * @param int|null $value
     *
     * @return static
     */
    public function setMaxlength(?int $value): static;


    /**
     * Returns the pattern for this textarea or text input column
     *
     * @return string|null
     */
    public function getPattern(): ?string;


    /**
     * Sets the pattern for this textarea or text input column
     *
     * @param string|null $value
     *
     * @return static
     */
    public function setPattern(?string $value): static;


    /**
     * Returns the tooltip for this column
     *
     * @return string|null
     */
    public function getTooltip(): ?string;


    /**
     * Sets  the tooltip for this column
     *
     * @param string|null $value
     *
     * @return static
     */
    public function setTooltip(?string $value): static;


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
     *
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
     *
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
     *
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
     *
     * @return static
     */
    public function setRows(?int $value): static;


    /**
     * Returns the default value for this column
     *
     * @return mixed
     */
    public function getDefault(): mixed;


    /**
     * Sets the default value for this column
     *
     * @param mixed $value
     *
     * @return static
     */
    public function setDefault(mixed $value): static;


    /**
     * Returns the initial default value for this column
     *
     * @return mixed
     */
    public function getInitialDefault(): mixed;


    /**
     * Sets the initial default value for this column
     *
     * @param mixed $value
     *
     * @return static
     */
    public function setInitialDefault(mixed $value): static;


    /**
     * Returns if this column should be stored with NULL in the database if empty
     *
     * @note Defaults to false
     * @return bool
     */
    public function getNullDb(): bool;


    /**
     * Sets if this column should be stored with NULL in the database if empty
     *
     * @note Defaults to false
     *
     * @param bool                       $value
     * @param string|float|int|bool|null $default
     *
     * @return static
     */
    public function setNullDb(bool $value, string|float|int|bool|null $default = null): static;


    /**
     * Returns if this column should be disabled if the value is NULL
     *
     * @note Defaults to false
     * @return bool
     */
    public function getNullDisabled(): bool;


    /**
     * Sets if this column should be disabled if the value is NULL
     *
     * @note Defaults to false
     *
     * @param bool|null $value
     *
     * @return static
     */
    public function setNullDisabled(?bool $value): static;


    /**
     * Returns if this column should be readonly if the value is NULL
     *
     * @note Defaults to false
     * @return bool
     */
    public function getNullReadonly(): bool;


    /**
     * Sets if this column should be readonly if the value is NULL
     *
     * @note Defaults to false
     *
     * @param bool|null $value
     *
     * @return static
     */
    public function setNullReadonly(?bool $value): static;


    /**
     * Returns the type for this element if the value is NULL
     *
     * @return string|null
     */
    public function getNullInputType(): ?string;


    /**
     * Sets the type for this element if the value is NULL
     *
     * @param EnumInputType|null $value
     *
     * @return static
     */
    public function setNullInputType(?EnumInputType $value): static;


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
     *
     * @return static
     */
    public function addValidationFunction(callable $function): static;


    /**
     * Returns the help text for this column
     *
     * @return string|null
     */
    public function getHelpText(): ?string;


    /**
     * Sets the help text for this column
     *
     * @param string|null $value
     *
     * @return static
     */
    public function setHelpText(?string $value): static;


    /**
     * Returns the help text group for this column
     *
     * @return string|null
     */
    public function getHelpGroup(): ?string;


    /**
     * Sets the help text group for this column
     *
     * @param string|null $value
     *
     * @return static
     */
    public function setHelpGroup(?string $value): static;


    /**
     * Validate this column according to the column definitions
     *
     * @param ValidatorInterface $validator
     * @param string|null        $prefix
     *
     * @return bool
     */
    public function validate(ValidatorInterface $validator, ?string $prefix): bool;


    /**
     * Returns if this column is ignored
     *
     * If this column is ignored, it will be accepted (and not cause validation exceptions by existing) but will be
     *  completely ignored. It will not generate any HTML, or allow it self to be saved, and the columns will not be
     *  stored in the source
     *
     * @note Defaults to false
     * @return bool|null
     * @see  Definition::getRender()
     */
    public function getIgnored(): ?bool;


    /**
     * Sets if this column is ignored
     *
     * If this column is ignored, it will be accepted (and not cause validation exceptions by existing) but will be
     * completely ignored. It will not generate any HTML, or allow it self to be saved, and the columns will not be
     * stored in the source
     *
     * @note Defaults to false
     *
     * @param bool|null $value
     *
     * @return static
     * @see  Definition::setRender()
     */
    public function setIgnored(?bool $value): static;


    /**
     * Returns if changes to the field result into an auto-submit
     *
     * @return bool
     */
    public function getAutoSubmit(): bool;


    /**
     * Returns if changes to the field result into an auto-submit
     *
     * @param bool|null $value
     *
     * @return static
     */
    public function setAutoSubmit(?bool $value): static;


    /**
     * Returns the column
     *
     * @return string|null
     */
    public function getColumn(): ?string;


    /**
     * Sets the column
     *
     * @param string|null $column
     *
     * @return static
     */
    public function setColumn(?string $column): static;


    /**
     * Returns what element should be displayed if the value of this entry is NULL
     *
     * @return EnumElement|null
     */
    public function getNullElement(): EnumElement|null;


    /**
     * Sets what element should be displayed if the value of this entry is NULL
     *
     * @param EnumElement|null $value
     *
     * @return static
     */
    public function setNullElement(EnumElement|null $value): static;


    /**
     * Returns the additional content for this component
     *
     * @return RenderInterface|callable|string|null
     */
    public function getAdditionalContent(): RenderInterface|callable|string|null;


    /**
     * Sets the additional content for this component
     *
     * @param RenderInterface|callable|string|null $prefix
     *
     * @return static
     */
    public function setAdditionalContent(RenderInterface|callable|string|null $prefix): static;

    /**
     * Returns if this input element has after buttons
     *
     * @return bool
     */
    public function hasAfterButtons(): bool;

    /**
     * Returns the modal after_buttons
     *
     * @return ButtonsInterface
     */
    public function getAfterButtons(): ButtonsInterface;

    /**
     * Sets the modal after_buttons
     *
     * @param ButtonsInterface|null $after_buttons
     *
     * @return static
     */
    public function setAfterButtons(?ButtonsInterface $after_buttons): static;

    /**
     * Sets the modal after_buttons
     *
     * @param ButtonInterface|null $button
     *
     * @return static
     */
    public function addAfterButton(?ButtonInterface $button): static;

    /**
     * Returns if this input element has before buttons
     *
     * @return bool
     */
    public function hasBeforeButtons(): bool;

    /**
     * Returns the modal before_buttons
     *
     * @return ButtonsInterface
     */
    public function getBeforeButtons(): ButtonsInterface;

    /**
     * Sets the modal before_buttons
     *
     * @param ButtonsInterface|null $before_buttons
     *
     * @return static
     */
    public function setBeforeButtons(?ButtonsInterface $before_buttons): static;

    /**
     * Sets the modal before_buttons
     *
     * @param ButtonInterface|null $button
     *
     * @return static
     */
    public function addBeforeButton(?ButtonInterface $button): static;
}
