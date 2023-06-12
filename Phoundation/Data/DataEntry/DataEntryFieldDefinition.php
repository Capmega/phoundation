<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry;

use Phoundation\Data\Traits\UsesNewField;
use Phoundation\Data\Validator\Interfaces\InterfaceDataValidator;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Web\Http\Html\Enums\InputType;
use Phoundation\Web\Http\Html\Interfaces\InputTypeExtendedInterface;
use Phoundation\Web\Http\Html\Interfaces\InputElementInterface;
use Phoundation\Web\Http\Html\Interfaces\InputTypeInterface;


/**
 * Class DataEntryFieldDefinition
 *
 * Contains the definitions for a single DataEntry object field
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
class DataEntryFieldDefinition implements Interfaces\DataEntryFieldDefinition
{
    use UsesNewField;


    /**
     * Validations to execute to ensure
     */
    protected array $validations = [];

    /**
     * Supported input element types
     *
     * @var array[] $supported_input_types
     */
    protected static array $supported_input_types = [
        'button',
        'checkbox',
        'color',
        'date',
        'datetime-local',
        'email',
        'file',
        'hidden',
        'image',
        'month',
        'numeric',
        'password',
        'radio',
        'range',
        'reset',
        'search',
        'submit',
        'tel',
        'text',
        'time',
        'url',
        'week'
    ];


    /**
     * Definitions for this DataEntryFieldDefinition
     *
     * FIELD          DATATYPE           DEFAULT VALUE  DESCRIPTION
     * value          mixed              null           The value for this entry
     * visible        boolean            true           If false, this key will not be shown on web, and be readonly
     * virtual        boolean            false          If true, this key will be visible and can be modified but it
     *                                                  won't exist in database. It instead will be used to generate
     *                                                  a different field
     * element        string|null        "input"        Type of element, input, select, or text or callable function
     * type           string|null        "text"         Type of input element, if element is "input"
     * readonly       boolean            false          If true, will make the input element readonly
     * disabled       boolean            false          If true, the field will be displayed as disabled
     * label          string|null        null           If specified, will show a description label in HTML
     * size           int [1-12]         12             The HTML boilerplate column size, 1 - 12 (12 being the whole
     *                                                  row)
     * source         array|string|null  null           Array or query source to get contents for select, or single
     *                                                  value for text inputs
     * execute        array|null         null           Bound execution variables if specified "source" is a query
     *                                                  string
     * complete       array|bool|null    null           If defined must be bool or contain array with key "noword"
     *                                                  and "word". each key must contain a callable function that
     *                                                  returns an array with possible words for shell auto
     *                                                  completion. If bool, the system will generate this array
     *                                                  automatically from the rows for this field
     * cli            string|null        null           If set, defines the alternative column name definitions for
     *                                                  use with CLI. For example, the column may be name, whilst
     *                                                  the cli column name may be "-n,--name"
     * optional       boolean            false          If true, the field is optional and may be left empty
     * title          string|null        null           The title attribute which may be used for tooltips
     * placeholder    string|null        null           The placeholder attribute which typically shows an example
     * maxlength      string|null        null           The maxlength attribute which typically shows an example
     * pattern        string|null        null           The pattern the value content should match in browser client
     * min            string|null        null           The minimum amount for numeric inputs
     * max            string|null        null           The maximum amount for numeric inputs
     * step           string|null        null           The up / down step for numeric inputs
     * default        mixed              null           If "value" for entry is null, then default will be used
     * null_disabled  boolean            false          If "value" for entry is null, then use this for "disabled"
     * null_readonly  boolean            false          If "value" for entry is null, then use this for "readonly"
     * null_type      boolean            false          If "value" for entry is null, then use this for "type"
     *
     * @var array
     */
    protected array $definitions = [];


    /**
     * Returns the internal definitions for this field
     *
     * @return array
     */
    public function getDefinitions(): array
    {
        return $this->definitions;
    }


    /**
     * Sets all the internal definitions for this field in one go
     *
     * @param array $definitions
     * @return static
     */
    public function setDefinitions(array $definitions): static
    {
        $this->definitions = $definitions;
        return $this;
    }


    /**
     * Add specified value for the specified key for this DataEntry field
     *
     * @param string $key
     * @return callable|string|float|int|bool|null
     */
    public function getKey(string $key): callable|string|float|int|bool|null
    {
        return isset_get($this->definitions[$key]);
    }


    /**
     * Add specified value for the specified key for this DataEntry field
     *
     * @param string $key
     * @param string|float|int|bool|null $value
     * @return static
     */
    public function setKey(string $key, callable|array|string|float|int|bool|null $value): static
    {
        $this->definitions[$key] = $value;
        return $this;
    }


    /**
     * Returns if this field is visible in HTML clients
     *
     * If false, the field will not be displayed and typically will be modified through a virtual field instead.
     *
     * @note Defaults to true
     * @return bool|null
     * @see DataEntryFieldDefinition::getVirtual()
     */
    public function getVisible(): ?bool
    {
        return isset_get_typed('bool', $this->definitions['visible']);
    }


    /**
     * Sets if this field is visible in HTML clients
     *
     * If false, the field will not be displayed and typically will be modified through a virtual field instead.
     *
     * @note Defaults to true
     * @see DataEntryFieldDefinition::setVirtual()
     * @param bool|null $value
     * @return static
     */
    public function setVisible(?bool $value): static
    {
        if ($value === null) {
            // Default
            $value = true;
        }

        return $this->setKey('visible', $value);
    }


    /**
     * Return if this field is a meta field
     *
     * If this field is a meta field, it will be readonly for user actions
     *
     * @note Defaults to false
     * @see DataEntryFieldDefinition::getVisible()
     * @return bool
     */
    public function getMeta(): bool
    {
        return isset_get_typed('bool', $this->definitions['meta'], false);
    }


    /**
     * Sets if this field is a meta field
     *
     * If this field is a meta field, it will be readonly for user actions
     *
     * @note Defaults to false
     * @see DataEntryFieldDefinition::setVisible()
     * @param bool $value
     * @return static
     */
    public function setMeta(bool $value): static
    {
        if ($value) {
            $this->setReadonly(true);
        }

        return $this->setKey('meta', $value);
    }


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
    public function getVirtual(): ?bool
    {
        return isset_get_typed('bool', $this->definitions['virtual']);
    }


    /**
     * Sets if this field is virtual
     *
     * If this field is virtual, it will be visible and can be manipulated but will have no direct database entry.
     * Instead, it will modify a different field. This is (for example) used in an entry that uses countries_id which
     * will be invisible whilst the virtual field "country" will modify countries_id
     *
     * @note Defaults to false
     * @see DataEntryFieldDefinition::setVisible()
     * @param bool|null $value
     * @return static
     */
    public function setVirtual(?bool $value): static
    {
        if ($value === null) {
            // Default
            $value = false;
        }

        return $this->setKey('virtual', $value);
    }


    /**
     * Returns the HTML client element to be used for this field
     *
     * @return string|null
     */
    public function getElement(): string|null
    {
        return isset_get_typed('string', $this->definitions['element']);
    }


    /**
     * Sets the HTML client element to be used for this field
     *
     * @param InputElementInterface|null $value
     * @return static
     */
    public function setElement(InputElementInterface|null $value): static
    {
        if (!empty($this->definitions['type'])) {
            if ($value !== 'input') {
                throw new OutOfBoundsException(tr('Cannot set element ":value" for field ":field" as the element type has already been set to ":type" and typed fields can only have the element "input"', [
                    ':value' => $value->value,
                    ':field' => $this->field,
                    ':type'  => $this->definitions['element']
                ]));
            }
        }

        return $this->setKey('element', $value->value);
    }


    /**
     * Returns the HTML client element to be used for this field
     *
     * @return callable|string|null
     */
    public function getContent(): callable|string|null
    {
        return isset_get_typed('callable|string', $this->definitions['element']);
    }


    /**
     * Sets the HTML client element to be used for this field
     *
     * @param callable|string|null $value
     * @return static
     */
    public function setContent(callable|string|null $value): static
    {
        return $this->setKey('content', $value);
    }


    /**
     * Return the type of input element.
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return isset_get_typed('string', $this->definitions['type']);
    }


    /**
     * Sets the type of input element.
     *
     * @param InputTypeInterface|InputTypeExtendedInterface|null $value
     * @return static
     */
    public function setInputType(InputTypeInterface|InputTypeExtendedInterface|null $value): static
    {
        if ($value) {
            if ($value instanceof InputTypeExtendedInterface) {
                // This is an extended virtual input type, adjust it to an existing input type.
                switch ($value->value) {
                    case 'dbid':
                        $value = InputType::numeric;

                        $this->addValidationFunction(function ($validator) {
                            $validator->isDbId();
                        });

                        break;

                    case 'natural':
                        $value = InputType::numeric;

                        $this->addValidationFunction(function ($validator) {
                            $validator->isNatural();
                        });

                        break;

                    case 'integer':
                        $value = InputType::numeric;

                        $this->addValidationFunction(function ($validator) {
                            $validator->isInteger();
                        });

                        break;

                    case 'float':
                        $value = InputType::numeric;

                        $this->addValidationFunction(function ($validator) {
                            $validator->isFloat();
                        });

                        break;

                    case 'name':
                        $value = InputType::text;

                        $this->addValidationFunction(function ($validator) {
                            $validator->isName();
                        });

                        break;

                    case 'username':
                        $value = InputType::text;

                        $this->addValidationFunction(function ($validator) {
                            $validator->isUsername();
                        });

                        break;

                    case 'code':
                        $value = InputType::text;

                        $this->addValidationFunction(function ($validator) {
                            $validator->isCode();
                        });

                        break;

                    case 'description':
                        $value = InputType::text;

                        $this->addValidationFunction(function ($validator) {
                            $validator->isDescription();
                        });

                        break;
                }
            }

            switch ($value->value) {
                case 'numeric':
                    // Numbers should never be longer than this
                    $this->setMaxlength(16);
            }

            $this->validateType('type', $value->value);
        }

        return $this->setKey('type', $value->value);
    }


    /**
     * Returns if the value cannot be modified and this element will be shown as disabled on HTML clients
     *
     * @note Defaults to false
     * @return bool|null
     */
    public function getReadonly(): ?bool
    {
        return isset_get_typed('bool', $this->definitions['readonly']);
    }


    /**
     * If true, the value cannot be modified and this element will be shown as disabled on HTML clients
     *
     * @note Defaults to false
     * @param bool|null $value
     * @return static
     */
    public function setReadonly(?bool $value): static
    {
        if ($value === null) {
            // Default
            $value = false;
        }

        return $this->setKey('readonly', $value);
    }


    /**
     * Returns if the value cannot be modified and this element will be shown as disabled on HTML clients
     *
     * @note Defaults to false
     * @return bool|null
     */
    public function getDisabled(): ?bool
    {
        return isset_get_typed('bool', $this->definitions['disabled']);
    }


    /**
     * If true, the value cannot be modified and this element will be shown as disabled on HTML clients
     *
     * @note Defaults to false
     * @param bool|null $value
     * @return static
     */
    public function setDisabled(?bool $value): static
    {
        if ($value === null) {
            // Default
            $value = false;
        }

        return $this->setKey('disabled', $value);
    }


    /**
     * The label to be shown on HTML clients
     *
     * @return string|null $value
     */
    public function getLabel(): ?string
    {
        return isset_get_typed('string', $this->definitions['label']);
    }


    /**
     * The label to be shown on HTML clients
     *
     * @param string|null $value
     * @return static
     */
    public function setLabel(?string $value): static
    {
        return $this->setKey('label', $value);
    }


    /**
     * Returns the boilerplate col size for this field, must be integer number between 1 and 12
     *
     * @return int|null
     */
    public function getSize(): ?int
    {
        return isset_get_typed('int', $this->definitions['size']);
    }


    /**
     * Sets the boilerplate col size for this field, must be integer number between 1 and 12
     *
     * @param int|null $value
     * @return static
     */
    public function setSize(?int $value): static
    {
        if ($value) {
            if ($this->getVirtual()) {
                throw new OutOfBoundsException(tr('Cannot define size for field ":field", this field is virtual and will not be displayed', [
                    ':field' => $this->field,
                ]));
            }

            if (($value < 1) or ($value > 12)) {
                throw new OutOfBoundsException(tr('Invalid size ":value" specified for field ":field", it must be an integer number between 1 and 12', [
                    ':field' => $this->field,
                    ':value' => $value
                ]));
            }
        }

        return $this->setKey('size', $value);
    }


    /**
     * Returns a data source for the HTML client element contents of this field
     *
     * The data source may be specified as a query string or a key => value array
     *
     * @return array|string|null
     */
    public function getSource(): array|string|null
    {
        return isset_get_typed('array|string', $this->definitions['source']);
    }


    /**
     * Sets a data source for the HTML client element contents of this field
     *
     * The data source may be specified as a query string or a key => value array
     *
     * @param array|string|null $value
     * @return static
     */
    public function setSource(array|string|null $value): static
    {
        if ($value) {
            if (is_string($value)) {
throw new UnderConstructionException();
            }

            $this->addValidationFunction(function ($validator) use ($value) {
                $validator->isInArray(array_keys($value));
            });
        }

        return $this->setKey('source', $value);
    }


    /**
     * Returns a query execute bound variables execute array for the specified query string source
     *
     * @note Requires "source" to be a query string
     * @return array|null
     */
    public function getExecute(): ?array
    {
        return isset_get_typed('array', $this->definitions['execute']);
    }


    /**
     * Sets a query execute bound variables execute array for the specified query string source
     *
     * @note Requires "source" to be a query string
     * @param array|string|null $value
     * @return static
     */
    public function setExecute(array|string|null $value): static
    {
        if (!array_key_exists('source', $this->definitions)) {
            throw new OutOfBoundsException(tr('Cannot specify execute array ":value" for field ":field", a data query string source must be specified first', [
                ':field' => $this->field,
                ':value' => $value
            ]));
        }

        if (is_array($this->definitions['source'])) {
            throw new OutOfBoundsException(tr('Cannot specify execute array ":value" for field ":field", the "source" must be a string query but is an array instead', [
                ':field' => $this->field,
                ':value' => $value
            ]));
        }

        return $this->setKey('execute', $value);
    }


    /**
     * Returns the cli auto-completion queries for this field
     *
     * @return array|bool|null
     */
    public function getAutoComplete(): array|bool|null
    {
        return isset_get_typed('array|bool', $this->definitions['auto_complete']);
    }


    /**
     * Sets the cli auto-completion queries for this field
     *
     * @param array|bool|null $value
     * @return static
     */
    public function setAutoComplete(array|bool|null $value): static
    {
        if ($value === false) {
            throw new OutOfBoundsException(tr('Invalid value "FALSE" specified for field ":field", it must be "TRUE" or an array with only the keys "word" and "noword"', [
                ':field' => $this->field,
            ]));
        }

        if (is_array($value)) {
            if (count($value) !== 2) {
                $fail = true;
            }

            if (!array_key_exists('word', $value) or !array_key_exists('noword', $value)) {
                $fail = true;
            }

            if (isset($fail)) {
                throw new OutOfBoundsException(tr('Invalid value ":value" specified for field ":field", it must be "TRUE" or an array with only the keys "word" and "noword"', [
                    ':field' => $this->field,
                    ':value' => $value
                ]));
            }
        }

        return $this->setKey('auto_complete', $value);
    }


    /**
     * Returns the alternative CLI field names for this field
     *
     * @return string
     */
    public function getCliField(): string
    {
        if (empty($this->definitions['cli_field'])) {
            return $this->field;
        }

        return isset_get_typed('string', $this->definitions['cli_field']);
    }


    /**
     * Sets the alternative CLI field names for this field
     *
     * @param string|null $value
     * @return static
     */
    public function setCliField(?string $value): static
    {
        return $this->setKey('cli_field', $value);
    }


    /**
     * Returns if this field is optional or not
     *
     * @note Defaults to false
     * @return bool|null
     */
    public function getOptional(): ?bool
    {
        return isset_get_typed('bool', $this->definitions['optional']);
    }


    /**
     * Sets if this field is optional or not
     *
     * @note Defaults to false
     * @param bool|null $value
     * @param string|float|int|bool|null $default
     * @return static
     */
    public function setOptional(?bool $value, string|float|int|bool|null $default = null): static
    {
        if ($value === null) {
            // Default
            $value = false;
        }

        $this->setKey('default', $default);
        $this->setKey('optional', $value);

        return $this;
    }


    /**
     * Returns the placeholder for this field
     *
     * @return string|null
     */
    public function getPlaceholder(): ?string
    {
        return isset_get_typed('string', $this->definitions['placeholder']);
    }


    /**
     * Sets the placeholder for this field
     *
     * @param string|null $value
     * @return static
     */
    public function setPlaceholder(?string $value): static
    {
        $this->validateTextTypeElement('placeholder', $value);
        return $this->setKey('placeholder', $value);
    }


    /**
     * Returns the minlength for this textarea or text input field
     *
     * @return int|null
     */
    public function getMinlength(): ?int
    {
        return isset_get_typed('int', $this->definitions['minlength']);
    }


    /**
     * Sets the minlength for this textarea or text input field
     *
     * @param int|null $value
     * @return static
     */
    public function setMinlength(?int $value): static
    {
        $this->validateTextTypeElement('minlength', $value);
        return $this->setKey('minlength', $value);
    }


    /**
     * Returns the maxlength for this textarea or text ibput field
     *
     * @return int|null
     */
    public function getMaxlength(): ?int
    {
        return isset_get_typed('int', $this->definitions['maxlength']);
    }


    /**
     * Sets the maxlength for this textarea or text input field
     *
     * @param int|null $value
     * @return static
     */
    public function setMaxlength(?int $value): static
    {
        return $this->setKey('maxlength', $value);
    }


    /**
     * Returns the pattern for this textarea or text input field
     *
     * @return string|null
     */
    public function getPattern(): ?string
    {
        return isset_get_typed('string', $this->definitions['pattern']);
    }


    /**
     * Sets the pattern for this textarea or text input field
     *
     * @param string|null $value
     * @return static
     */
    public function setPattern(?string $value): static
    {
        $this->validateTextTypeElement('pattern', $value);
        return $this->setKey('pattern', $value);
    }


    /**
     * Returns the minimum value for number input elements
     *
     * @return float|int|null
     */
    public function getMin(): float|int|null
    {
        return isset_get_typed('float|int', $this->definitions['min'], 0);
    }


    /**
     * Set the minimum value for number input elements
     *
     * @param float|int|null $value
     * @return static
     */
    public function setMin(float|int|null $value): static
    {
        $this->validateNumberTypeInput('min', $value);
        return $this->setKey('min', $value);
    }


    /**
     * Returns the maximum value for number input elements
     *
     * @return float|int|null
     */
    public function getMax(): float|int|null
    {
        return isset_get_typed('float|int', $this->definitions['max']);
    }


    /**
     * Set the maximum value for number input elements
     *
     * @param float|int|null $value
     * @return static
     */
    public function setMax(float|int|null $value): static
    {
        $this->validateNumberTypeInput('max', $value);
        return $this->setKey('max', $value);
    }


    /**
     * Return the step value for number input elements
     *
     * @return string|float|int|null
     */
    public function getStep(): string|float|int|null
    {
        return isset_get_typed('string|float|int', $this->definitions['step'], 1);
    }


    /**
     * Set the step value for number input elements
     *
     * @param string|float|int|null $value
     * @return static
     */
    public function setStep(string|float|int|null $value): static
    {
        $this->validateNumberTypeInput('step', $value);
        return $this->setKey('step', $value);
    }


    /**
     * Returns the rows value for textarea elements
     *
     * @return int|null
     */
    public function getRows(): int|null
    {
        return isset_get_typed('int', $this->definitions['rows']);
    }


    /**
     * Sets the rows value for textarea elements
     *
     * @param int|null $value
     * @return static
     */
    public function setRows(int|null $value): static
    {
        if (isset_get($this->definitions['element']) !== 'textarea') {
            throw new OutOfBoundsException(tr('Cannot define rows for field ":field", the element is a ":element" but should be a "textarea', [
                ':field'   => $this->field,
                ':element' => $value,
            ]));
        }

        return $this->setKey('rows', $value);
    }


    /**
     * Returns the default value for this field
     *
     * @return string|float|int|bool|null
     */
    public function getDefault(): string|float|int|bool|null
    {
        return isset_get_typed('string|float|int|bool', $this->definitions['default']);
    }


    /**
     * Sets the default value for this field
     *
     * @param string|float|int|bool|null $value
     * @return static
     */
    public function setDefault(string|float|int|bool|null $value): static
    {
        return $this->setKey('default', $value);
    }


    /**
     * Returns the default value for this field in the database
     *
     * @return string|float|int|null
     */
    public function getDefaultDb(): string|float|int|null
    {
        return isset_get_typed('string|float|int', $this->definitions['default_db']);
    }


    /**
     * Sets the default value for this field in the database
     *
     * @param string|float|int|null $value
     * @return static
     */
    public function setDefaultDb(string|float|int|null $value): static
    {
        return $this->setKey('default_db', $value);
    }


    /**
     * Returns if this field should be stored with NULL in the database if empty
     *
     * @note Defaults to false
     * @return bool
     */
    public function getNullDb(): bool
    {
        return isset_get_typed('bool', $this->definitions['null_db'], true);
    }


    /**
     * Sets if this field should be stored with NULL in the database if empty
     *
     * @note Defaults to false
     * @param bool $value
     * @param string|float|int|null $default
     * @return static
     */
    public function setNullDb(bool $value, string|float|int|null $default = null): static
    {
        $this->setKey('null_db'   , $value);
        $this->setKey('default_db', $value);

        return $this;
    }


    /**
     * Returns if this field should be disabled if the value is NULL
     *
     * @note Defaults to false
     * @return bool
     */
    public function getNullDisabled(): bool
    {
        return isset_get_typed('bool', $this->definitions['null_disabled'], false);
    }


    /**
     * Sets if this field should be disabled if the value is NULL
     *
     * @note Defaults to false
     * @param bool|null $value
     * @return static
     */
    public function setNullDisabled(?bool $value): static
    {
        if ($value === null) {
            // Default
            $value = false;
        }

        return $this->setKey('null_disabled', $value);
    }


    /**
     * Returns if this field should be readonly if the value is NULL
     *
     * @note Defaults to false
     * @return bool
     */
    public function getNullReadonly(): bool
    {
        return isset_get_typed('bool', $this->definitions['null_readonly'], false);
    }


    /**
     * Sets if this field should be readonly if the value is NULL
     *
     * @note Defaults to false
     * @param bool|null $value
     * @return static
     */
    public function setNullReadonly(?bool $value): static
    {
        if ($value === null) {
            // Default
            $value = false;
        }

        return $this->setKey('null_readonly', $value);
    }


    /**
     * Returns the type for this element if the value is NULL
     *
     * @return string|null
     */
    public function getNullType(): ?string
    {
        return isset_get_typed('string', $this->definitions['null_type']);
    }


    /**
     * Sets the type for this element if the value is NULL
     *
     * @param InputType|null $value
     * @return static
     */
    public function setNullInputType(?InputType $value): static
    {
        $this->validateType('type', $value->value);
        return $this->setKey('type', $value->value);
    }


    /**
     * Returns the type for this element if the value is NULL
     *
     * @return array|null
     */
    public function getValidationFunctions(): ?array
    {
        return isset_get_typed('array', $this->definitions['validation_functions']);
    }


    /**
     * Sets the type for this element if the value is NULL
     *
     * @param callable $function
     * @return static
     */
    public function addValidationFunction(callable $function): static
    {
        $this->validations[] = $function;
        return $this;
    }


    /**
     * Returns the help text for this field
     *
     * @return string|null
     */
    public function getHelpText(): ?string
    {
        return isset_get_typed('string', $this->definitions['help_text']);
    }


    /**
     * Sets the help text for this field
     *
     * @param string|null $value
     * @return static
     */
    public function setHelpText(?string $value): static
    {
        return $this->setKey('help_text', trim($value));
    }


    /**
     * Returns the help text group for this field
     *
     * @return string|null
     */
    public function getHelpGroup(): ?string
    {
        return isset_get_typed('string', $this->definitions['help_group']);
    }


    /**
     * Sets the help text group for this field
     *
     * @param string|null $value
     * @return static
     */
    public function setHelpGroup(?string $value): static
    {
        return $this->setKey('help_group', $value);
    }


    /**
     * Returns true if the specified input type is supported
     *
     * @param string $type
     * @return bool
     */
    public function inputTypeSupported(string $type): bool
    {
        return in_array($type, self::$supported_input_types);
    }


    /**
     * Validate this field according to the field definitions
     *
     * @param InterfaceDataValidator $validator
     * @return void
     */
    public function validate(InterfaceDataValidator $validator): void
    {
        if ($this->getReadonly() or $this->getDisabled() or $this->getMeta()) {
            // This field cannot be modified, plain ignore it.
            return;
        }

        // Checkbox inputs always are boolean
        $bool = ($this->getType() === 'checkbox');

        // Select the field
//show($this->getCliField() . ' ' . ($bool ? 'BOOL' : 'NEXT'));
        $validator->select($this->getCliField(), !$bool);

        // Apply default validations
        if ($this->getOptional()) {
            $validator->isOptional($this->getDefault());
        }

        if ($bool) {
            $validator->isBoolean();

        } else {
            switch ($this->getElement()) {
                case 'textarea':
                    // Validate textarea strings
                    if ($this->getMinlength()) {
                        $validator->hasMinCharacters($this->getMinlength());
                    }

                    if ($this->getMaxlength()) {
                        $validator->hasMaxCharacters($this->getMaxlength());
                    }

                    break;

                case 'input':
                    switch ($this->getType()) {
                        case 'date':
                            $validator->isDate();
                            break;

                        case 'color':
                            $validator->isColor();
                            break;

                        case 'tel':
                            $validator->isPhoneNumber();
                            break;

                        case 'email':
                            $validator->isEmail();
                            break;

                        case 'time':
                            $validator->isTime();
                            break;

                        case 'datetime-local':
                            $validator->isDateTime();
                            break;

                        case 'numeric':
                        case 'year':
                        case 'month':
                        case 'week':
                        case 'day':
                            // Validate numbers
                            if ($this->getMin()) {
                                $validator->isMoreThan($this->getMin());
                            }

                            if ($this->getMax()) {
                                $validator->isLessThan($this->getMax());
                            }

                            break;

                        default:
                            // Validate input text strings
                            if ($this->getMinlength()) {
                                $validator->hasMinCharacters($this->getMinlength());
                            }

                            if ($this->getMaxlength()) {
                                $validator->hasMaxCharacters($this->getMaxlength());
                            }
                    }

                    break;

                case 'select':
            }

            $source = $this->getSource();

            if ($source) {
                if (is_array($source)) {
                    // The value must be in the specified source
                    $validator->isInArray($source);
                }
            }
        }

        // All other validations
        foreach ($this->validations as $validation) {
            $validation($validator, );
        }
    }


    /**
     * Ensures that the current field uses a text type input element or textarea element
     *
     * @param string $key
     * @param string|float|int|null $value
     * @return void
     */
    protected function validateTextTypeElement(string $key, string|float|int|null $value): void
    {
        if (is_callable(isset_get($this->definitions['element']))) {
            // We can't validate data types for this since it's a callback function
            return;
        }

        switch (isset_get($this->definitions['element'])) {
            case 'textarea':
                // no break
            case 'select':
                break;

            case null:
                // This is the default, so "input"
            case 'input':
                if (!array_key_exists('type', $this->definitions) or in_array($this->definitions['type'], ['text', 'email', 'url', 'password'])) {
                    break;
                }

                throw new OutOfBoundsException(tr('Cannot set :attribute ":value" for field ":field", it is an ":type" type input element, :attribute can only be used for textarea elements or input elements with "text" type', [
                    ':attribute' => $key,
                    ':field'     => $this->field,
                    ':type'      => $this->definitions['type'] ?? 'text',
                      ':value'     => $value
                ]));

            default:
                throw new OutOfBoundsException(tr('Cannot set :attribute ":value" for field ":field", it is an ":element" element, :attribute can only be used for textarea elements or input elements with "text" type', [
                    ':attribute' => $key,
                    ':field'     => $this->field,
                    ':element'   => $this->definitions['element'],
                    ':value'     => $value
                ]));
        }
    }


    /**
     * Ensures that the current field uses a number type input element
     *
     * @note This method considers number the following input types: number, range, date, datetime-local, time, week,
     *       month
     * @param string $key
     * @param string|float|int $value
     * @return void
     */
    protected function validateNumberTypeInput(string $key, string|float|int $value): void
    {
        if (is_callable(isset_get($this->definitions['element']))) {
            // We can't validate data types for this since it's a callback function
            return;
        }

        if (isset_get($this->definitions['element'], 'input') !== 'input') {
            throw new OutOfBoundsException(tr('Cannot set :attribute ":value" for field ":field", it is an ":element" element, :attribute can only be used for "number" type input elements', [
                ':attribute' => $key,
                ':field'     => $this->field,
                ':element'   => $this->definitions['element'],
                ':value'     => $value
            ]));
        }

        switch (isset_get($this->definitions['type'], 'text')) {
            case 'numeric':
                // no break
            case 'range':
                // no break
            case 'date':
                // no break
            case 'datetime-local':
                // no break
            case 'month':
            // no break
            case 'week':
            // no break
            case 'time':
                break;

            default:
                throw new OutOfBoundsException(tr('Cannot set :attribute ":value" for field ":field", it is an ":type" type input element, :attribute can only be used for "number" type input elements', [
                    ':attribute' => $key,
                    ':field'     => $this->field,
                    ':type'      => $this->definitions['type'] ?? 'text',
                    ':value'     => $value
                ]));
        }
    }


    /**
     * Verifies if the specified type is valid or not
     *
     * @param string $key
     * @param string|null $value
     * @return void
     */
    protected function validateType(string $key, ?string $value): void
    {
        if ($value === null) {
            $value = 'text';
        }

        if (empty($this->definitions['element'])) {
            $this->definitions['element'] = 'input';
        }

        if ($this->definitions['element'] !== 'input') {
            throw new OutOfBoundsException(tr('Cannot set :key ":value" for field ":field" as the element must be input (or empty, default) but is ":element"', [
                ':key'     => $key,
                ':type'    => $value,
                ':field'   => $this->field,
                ':element' => $this->definitions['element']
            ]));
        }

        if (!$this->inputTypeSupported($value)) {
            throw new OutOfBoundsException(tr('Cannot set :key ":value" for field ":field", only the types ":types" are supported', [
                ':key'   => $key,
                ':value' => $value,
                ':field' => $this->field,
                ':types' => self::$supported_input_types
            ]));
        }
    }
}