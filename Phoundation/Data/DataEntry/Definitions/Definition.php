<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Definitions;

use PDOStatement;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\Traits\DataColumn;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Databases\Sql\Interfaces\SqlQueryInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Web\Html\Components\Interfaces\InputElementInterface;
use Phoundation\Web\Html\Components\Interfaces\InputTypeExtendedInterface;
use Phoundation\Web\Html\Components\Interfaces\InputTypeInterface;
use Phoundation\Web\Html\Enums\InputElement;
use Phoundation\Web\Html\Enums\InputType;
use Phoundation\Web\Html\Enums\InputTypeExtended;
use Phoundation\Web\Html\Html;
use Stringable;
use Throwable;


/**
 * Class Definition
 *
 * Contains the definitions for a single DataEntry object column
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
class Definition implements DefinitionInterface
{
    use DataColumn;


    /**
     * The data entry where this definition belongs to
     *
     * @var DataEntryInterface|null $data_entry
     */
    protected ?DataEntryInterface $data_entry;

    /**
     * Validations to execute to ensure
     */
    protected array $validations = [];

    /**
     * The prefix that is automatically added to this value, after validation
     *
     * @var string|null $prefix
     */
    protected ?string $prefix = null;

    /**
     * The postfix that is automatically added to this value, after validation
     *
     * @var string|null $postfix
     */
    protected ?string $postfix = null;

    /**
     * These keys should not ever be processed
     *
     * @var array $meta_columns
     */
    protected static array $meta_columns = [
        'id',
        'created_by',
        'created_on',
        'status',
        'meta_id',
        'meta_state',
    ];

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
        'number',
        'password',
        'radio',
        'range',
        'reset',
        'search',
        'select',
        'submit',
        'tel',
        'text',
        'time',
        'url',
        'week',
        'auto-suggest',
        'array_json'
    ];

    /**
     * Definitions for this Definition
     *
     * FIELD          DATATYPE           DEFAULT VALUE  DESCRIPTION
     * value          mixed              null           The value for this entry
     * visible        boolean            true           If false, this key will not be shown on web, and be readonly
     * virtual        boolean            false          If true, this key will be visible and can be modified but it
     *                                                  won't exist in database. It instead will be used to generate
     *                                                  a different column
     * element        string|null        "input"        Type of element, input, select, or text or callable function
     * type           string|null        "text"         Type of input element, if element is "input"
     * readonly       boolean            false          If true, will make the input element readonly
     * disabled       boolean            false          If true, the column will be displayed as disabled
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
     *                                                  automatically from the rows for this column
     * cli            string|null        null           If set, defines the alternative column name definitions for
     *                                                  use with CLI. For example, the column may be name, whilst
     *                                                  the cli column name may be "-n,--name"
     * optional       boolean            false          If true, the column is optional and may be left empty
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
    protected array $rules = [];


    /**
     * UsesNewColumn class constructor
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null $column
     */
    public function __construct(?DataEntryInterface $data_entry, ?string $column = null)
    {
        $this->data_entry = $data_entry;
        $this->column      = $column;
    }


    /**
     * Returns a new static object
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null $column
     * @return DefinitionInterface
     */
    public static function new(?DataEntryInterface $data_entry, ?string $column = null): DefinitionInterface
    {
        return new static($data_entry, $column);
    }


    /**
     * Returns the query builder from the data entry
     *
     * @return QueryBuilderInterface
     */
    public function getQueryBuilder(): QueryBuilderInterface
    {
        return $this->data_entry->getQueryBuilder();
    }


    /**
     * Modify the contents of the query builder through a callback function
     *
     * @param callable $callback
     * @return $this
     */
    public function modifyQueryBuilder(callable $callback): static
    {
        $callback($this->data_entry->getQueryBuilder());
        return $this;
    }


    /**
     * Returns the internal definitions for this column
     *
     * @return array
     */
    public function getRules(): array
    {
        return $this->rules;
    }


    /**
     * Sets all the internal definitions for this column in one go
     *
     * @param array $rules
     * @return static
     */
    public function setRules(array $rules): static
    {
        $this->rules = $rules;
        return $this;
    }


    /**
     * Returns the prefix that is automatically added to this value, after validation
     *
     * @return string|null
     */
    public function getPrefix(): ?string
    {
        return $this->prefix;
    }


    /**
     * Sets the prefix that is automatically added to this value, after validation
     *
     * @param string|null $prefix
     * @return static
     */
    public function setPrefix(?string $prefix): static
    {
        $this->prefix = $prefix;
        return $this;
    }


    /**
     * Returns the postfix that is automatically added to this value, after validation
     *
     * @return string|null
     */
    public function getPostfix(): ?string
    {
        return $this->postfix;
    }


    /**
     * Sets the postfix that is automatically added to this value, after validation
     *
     * @param string|null $postfix
     * @return static
     */
    public function setPostfix(?string $postfix): static
    {
        $this->postfix = $postfix;
        return $this;
    }


    /**
     * Add specified value for the specified key for this DataEntry column
     *
     * @param string $key
     * @return mixed
     */
    public function getKey(string $key): mixed
    {
        return isset_get($this->rules[$key]);
    }


    /**
     * Add specified value for the specified key for this DataEntry column
     *
     * @param mixed $value
     * @param string $key
     * @return static
     */
    public function setKey(mixed $value, string $key): static
    {
        $this->rules[$key] = $value;
        return $this;
    }


    /**
     * Returns if this column is visible in HTML clients
     *
     * If false, the column will not be displayed and typically will be modified through a virtual column instead.
     *
     * @note Defaults to true
     * @return bool|null
     * @see Definition::getVirtual()
     */
    public function getVisible(): ?bool
    {
        return isset_get_typed('bool', $this->rules['visible'], true);
    }


    /**
     * Sets if this column is visible in HTML clients
     *
     * If false, the column will not be displayed and typically will be modified through a virtual column instead.
     *
     * @note Defaults to true
     * @param bool|null $value
     * @return static
     * @see Definition::setVirtual()
     */
    public function setVisible(?bool $value): static
    {
        if ($value === null) {
            // Default
            $value = true;
        }

        return $this->setKey($value, 'visible');
    }


    /**
     * Returns the extra HTML classes for this DataEntryForm object
     *
     * @param bool $add_prefixless_names
     * @return array
     * @see Definition::getVirtual()
     */
    public function getClasses(bool $add_prefixless_names = true): array
    {
        $classes = isset_get_typed('array', $this->rules['classes'], []);

        if ($add_prefixless_names) {
            // Add the column name without prefix as a class name
            $classes[] = $this->column;
        }

        return $classes;
    }


    /**
     * Adds the specified HTML classes to the DataEntryForm object
     *
     * @note When specifying multiple classes in a string, make sure they are space separated!
     *
     * @param array|string $value
     * @return static
     * @see Definition::setVirtual()
     */
    public function addClasses(array|string $value): static
    {
        $value   = Arrays::force($value, ' ');
        $value   = array_merge($this->getClasses(), $value);

        return $this->setKey($value, 'classes');
    }


    /**
     * Returns if this column will not set the DataEntry to "modified" state when changed
     *
     * @note Defaults to true
     * @return bool|null
     */
    public function getIgnoreModify(): ?bool
    {
        return isset_get_typed('bool', $this->rules['ignore_modify'], false);
    }


    /**
     * Sets if this column will not set the DataEntry to "modified" state when changed
     *
     * @note Defaults to false
     * @param bool|null $value
     * @return static
     */
    public function setIgnoreModify(?bool $value): static
    {
        return $this->setKey((bool) $value, 'ignore_modify');
    }


    /**
     * Return if this column is a meta column
     *
     * If this column is a meta column, it will be readonly for user actions
     *
     * @note Defaults to false
     * @return bool
     * @see Definition::getVisible()
     */
    public function isMeta(): bool
    {
        return in_array($this->column, static::$meta_columns);
    }


    /**
     * Returns if this column is virtual
     *
     * If this column is virtual, it will be visible and can be manipulated but will have no direct database entry.
     * Instead, it will modify a different column. This is (for example) used in an entry that uses countries_id which
     * will be invisible whilst the virtual column "country" will modify countries_id
     *
     * @note Defaults to false
     * @return bool|null
     *@see Definition::getVisible()
     */
    public function getVirtual(): ?bool
    {
        return isset_get_typed('bool', $this->rules['virtual'], false);
    }


    /**
     * Sets if this column is virtual
     *
     * If this column is virtual, it will be visible and can be manipulated but will have no direct database entry.
     * Instead, it will modify a different column. This is (for example) used in an entry that uses countries_id which
     * will be invisible whilst the virtual column "country" will modify countries_id
     *
     * @note Defaults to false
     * @param bool|null $value
     * @return static
     * @see Definition::setVisible()
     */
    public function setVirtual(?bool $value): static
    {
        return $this->setKey((bool) $value, 'virtual');
    }


    /**
     * Returns if this column is ignored
     *
     * If this column is ignored, it will be accepted (and not cause validation exceptions by existing) but will be
     *  completely ignored. It will not generate any HTML, or allow it self to be saved, and the columns will not be
     *  stored in the source
     *
     * @note Defaults to false
     * @return bool|null
     *@see Definition::getVisible()
     */
    public function getIgnored(): ?bool
    {
        return isset_get_typed('bool', $this->rules['ignored'], false);
    }


    /**
     * Sets if this column is ignored
     *
     * If this column is ignored, it will be accepted (and not cause validation exceptions by existing) but will be
     * completely ignored. It will not generate any HTML, or allow it self to be saved, and the columns will not be
     * stored in the source
     *
     * @note Defaults to false
     * @param bool|null $value
     * @return static
     * @see Definition::setVisible()
     */
    public function setIgnored(?bool $value): static
    {
        return $this->setKey((bool) $value, 'ignored');
    }


    /**
     * Returns if this column updates directly, bypassing DataEntry::setSourceValue()
     *
     * @note Defaults to false
     * @return bool|null
     *@see Definition::getVisible()
     */
    public function getDirectUpdate(): ?bool
    {
        return isset_get_typed('bool', $this->rules['direct_update'], false);
    }


    /**
     * Sets if this column updates directly, bypassing DataEntry::setSourceValue()
     *
     * @note Defaults to false
     * @param bool|null $value
     * @return static
     * @see Definition::setVisible()
     */
    public function setDirectUpdate(?bool $value): static
    {
        return $this->setKey((bool) $value, 'direct_update');
    }


    /**
     * Returns the static value for this column
     *
     * @return callable|string|float|int|bool|null
     */
    public function getValue(): callable|string|float|int|bool|null
    {
        return isset_get($this->rules['value']);
    }


    /**
     * Sets static value for this column
     *
     * @param callable|string|float|int|bool|null $value
     * @param bool $only_when_new = false
     * @return static
     */
    public function setValue(callable|string|float|int|bool|null $value, bool $only_when_new = false): static
    {
        if ($only_when_new and !$this->data_entry->isNew()) {
            // Don't set this value, only set it on new entries
            return $this;
        }

        return $this->setKey($value, 'value');
    }


    /**
     * Returns the auto focus for this column
     *
     * @return bool
     */
    public function getAutoFocus(): bool
    {
        return isset_get_typed('bool', $this->rules['auto_focus'], false);
    }


    /**
     * Sets the auto focus for this column
     *
     * @param bool $auto_focus
     * @return static
     */
    public function setAutoFocus(bool $auto_focus): static
    {
        return $this->setKey($auto_focus, 'auto_focus');
    }


    /**
     * Returns the HTML client element to be used for this column
     *
     * @return string|null
     */
    public function getElement(): string|null
    {
        return isset_get_typed('string', $this->rules['element']);
    }


    /**
     * Sets the HTML client element to be used for this column
     *
     * @param InputElementInterface|null $value
     * @return static
     */
    public function setElement(InputElementInterface|null $value): static
    {
        if (!empty($this->rules['type'])) {
            if ($value !== 'input') {
                throw new OutOfBoundsException(tr('Cannot set element ":value" for column ":column" as the element type has already been set to ":type" and typed columns can only have the element "input"', [
                    ':value' => $value?->value,
                    ':column' => $this->column,
                    ':type'  => $this->rules['element']
                ]));
            }
        }

        return $this->setKey($value?->value, 'element');
    }


    /**
     * Returns the HTML client element to be used for this column
     *
     * @return callable|string|null
     */
    public function getContent(): callable|string|null
    {
        return isset_get_typed('callable|string', $this->rules['content']);
    }


    /**
     * Sets the HTML client element to be used for this column
     *
     * @param callable|string|null $value
     * @param bool $make_safe
     * @return static
     */
    public function setContent(callable|string|null $value, bool $make_safe = false): static
    {
        if ($make_safe and !is_callable($value)) {
            $value = Html::safe($value);
        }

        return $this->setKey($value, 'content');
    }


    /**
     * Return the type of input element.
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return isset_get_typed('string', $this->rules['type']);
    }


    /**
     * Returns true if the input type is scalar, false if it is not
     *
     * @return bool
     */
    public function inputTypeIsScalar(): bool
    {
        switch ($this->getInputType()?->value) {
            case 'array_json':
                return false;

            default:
                return true;
        }
    }


    /**
     * Sets the type of input element.
     *
     * @return InputTypeExtendedInterface|InputTypeInterface|null
     */
    public function getInputType(): InputTypeExtendedInterface|InputTypeInterface|null
    {
        $return = $this->getKey('type');

        if ($return === null) {
            return null;
        }

        try {
            return InputType::from($return);

        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'is not a valid backing value for enum')) {
                // So the input type is not from InputTypeInterface, it must be from InputTypeExtendedInterface
                return InputTypeExtended::from($return);
            }

            // WTF else could possibly have happened?
            throw $e;
        }
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
                switch ($value) {
                    case InputTypeExtended::dbid:
                        $value = InputType::number;

                        $this->addValidationFunction(function (ValidatorInterface $validator) {
                            $validator->isNatural();
                        });

                        break;

                    case InputTypeExtended::natural:
                        $value = InputType::number;

                        $this->setKey($value->value, 'type');
                        $this->setMin(0);
                        $this->addValidationFunction(function (ValidatorInterface $validator) {
                            $validator->isNatural();
                        });

                        break;

                    case InputTypeExtended::integer:
                        $value = InputType::number;

                        $this->addValidationFunction(function (ValidatorInterface $validator) {
                            $validator->isInteger();
                        });

                        break;

                    case InputTypeExtended::positiveInteger:
                        $value = InputType::number;

                        $this->addValidationFunction(function (ValidatorInterface $validator) {
                            $validator->isInteger()->isMoreThan(0, true);
                        });

                        break;

                    case InputTypeExtended::negativeInteger:
                        $value = InputType::number;

                        $this->addValidationFunction(function (ValidatorInterface $validator) {
                            $validator->isInteger()->isLessThan(0, true);
                        });

                        break;

                    case InputTypeExtended::float:
                        $value = InputType::number;

                        $this->addValidationFunction(function (ValidatorInterface $validator) {
                            $validator->isFloat();
                        });

                        break;

                    case InputTypeExtended::name:
                        $value = InputType::text;

                        $this->addValidationFunction(function (ValidatorInterface $validator) {
                            $validator->isName();
                        });

                        break;

                    case InputTypeExtended::variable:
                        $value = InputType::text;
                        break;

                    case InputType::email:
                        $this->setMaxlength(128)->addValidationFunction(function (ValidatorInterface $validator) {
                            $validator->isEmail();
                        });

                        break;

                    case InputTypeExtended::url:
                        $value = InputType::text;

                        $this->addValidationFunction(function (ValidatorInterface $validator) {
                            $validator->isUrl();
                        });

                        break;

                    case InputTypeExtended::phone:
                        $value = InputType::tel;

                        $this->addValidationFunction(function (ValidatorInterface $validator) {
                            $validator->sanitizePhoneNumber();
                        });

                        break;

//                    case InputTypeExtended::phones:
//                        $value = InputType::text;
//
//                        $this->addValidationFunction(function (ValidatorInterface $validator) {
//                            $validator->isPhoneNumbers();
//                        });
//
//                        break;

                    case InputTypeExtended::username:
                        $value = InputType::text;

                        $this->addValidationFunction(function (ValidatorInterface $validator) {
                            $validator->isUsername();
                        });

                        break;

                    case InputTypeExtended::path:
                        $value = InputType::text;

                        $this->addValidationFunction(function (ValidatorInterface $validator) {
                            $validator->isDirectory();
                        });

                        break;

                    case InputTypeExtended::file:
                        $value = InputType::text;

                        $this->addValidationFunction(function (ValidatorInterface $validator) {
                            $validator->isFile();
                        });

                        break;

                    case InputTypeExtended::code:
                        $value = InputType::text;

                        $this->addValidationFunction(function (ValidatorInterface $validator) {
                            $validator->isCode();
                        });

                        break;

                    case InputTypeExtended::description:
                        $this->setElement(InputElement::textarea);

                        $this->addValidationFunction(function (ValidatorInterface $validator) {
                            $validator->isDescription();
                        });

                        // Don't set the value, textarea does not have an input type
                        return $this;

                    case InputTypeExtended::boolean:
                        $this->setElement(InputElement::input);
                        $this->setInputType(InputType::checkbox);

                        $this->addValidationFunction(function (ValidatorInterface $validator) {
                            $validator->isBoolean();
                        });

                        // Don't set the value, textarea does not have an input type
                        return $this;
                }
            }

            switch ($value) {
                case InputType::number:
                    // Numbers should never be longer than this
                    $this->setMaxlength(24);
            }

            $this->validateType('type', $value->value);
            return $this->setKey($value->value, 'type');
        }

        // NULL specified
        return $this->setKey(null, 'type');
    }


    /**
     * Returns if the value cannot be modified and this element will be shown as disabled on HTML clients
     *
     * @note Defaults to false
     * @return bool|null
     */
    public function getReadonly(): ?bool
    {
        return in_array($this->column, static::$meta_columns) or isset_get_typed('bool', $this->rules['readonly'], false);
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
        return $this->setKey((bool) $value, 'readonly');
    }


    /**
     * Returns if the entry is hidden (and will be rendered as a hidden element)
     *
     * @note Defaults to false
     * @return bool|null
     */
    public function getHidden(): ?bool
    {
        return isset_get_typed('bool', $this->rules['hidden'], false);
    }


    /**
     * Sets if the entry is hidden (and will be rendered as a hidden element)
     *
     * @note Defaults to false
     * @param bool|null $value
     * @return static
     */
    public function setHidden(?bool $value): static
    {
        return $this->setKey((bool) $value, 'hidden');
    }


    /**
     * If true, will enable browser auto suggest for this input control
     *
     * @note Defaults to false
     * @return bool
     */
    public function getAutoComplete(): bool
    {
        return isset_get_typed('bool', $this->rules['autocomplete'], true);
    }


    /**
     * If true, will enable browser auto suggest for this input control
     *
     * @note Defaults to false
     * @param bool|null $value
     * @return static
     */
    public function setAutoComplete(?bool $value): static
    {
        return $this->setKey((bool) $value, 'autocomplete');
    }


    /**
     * Returns if the value cannot be modified and this element will be shown as disabled on HTML clients
     *
     * @note Defaults to false
     * @return bool|null
     */
    public function getDisabled(): ?bool
    {
        return in_array($this->column, static::$meta_columns) or isset_get_typed('bool', $this->rules['disabled'], false);
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
        return $this->setKey((bool) $value, 'disabled');
    }


    /**
     * The label to be shown on HTML clients
     *
     * @return string|null $value
     */
    public function getLabel(): ?string
    {
        return isset_get_typed('string', $this->rules['label']);
    }


    /**
     * The label to be shown on HTML clients
     *
     * @param string|null $value
     * @return static
     */
    public function setLabel(?string $value): static
    {
        return $this->setKey($value, 'label');
    }


    /**
     * Returns the boilerplate col size for this column, must be integer number between 1 and 12
     *
     * @return int|null
     */
    public function getSize(): ?int
    {
        return isset_get_typed('int', $this->rules['size']);
    }


    /**
     * Sets the boilerplate col size for this column, must be integer number between 1 and 12
     *
     * @param int|null $value
     * @return static
     */
    public function setSize(?int $value): static
    {
        if ($value) {
            if (($value < 1) or ($value > 12)) {
                throw new OutOfBoundsException(tr('Invalid size ":value" specified for column ":column", it must be an integer number between 1 and 12', [
                    ':column' => $this->column,
                    ':value' => $value
                ]));
            }
        }

        return $this->setKey($value, 'size');
    }


    /**
     * Returns if changes to the field result into an auto-submit
     *
     * @return bool
     */
    public function getAutoSubmit(): bool
    {
        return (bool) isset_get_typed('bool', $this->rules['auto_submit']);
    }


    /**
     * Returns if changes to the field result into an auto-submit
     *
     * @param bool|null $value
     * @return static
     */
    public function setAutoSubmit(?bool $value): static
    {
        return $this->setKey((bool) $value, 'auto_submit');
    }


    /**
     * Returns a data source for the HTML client element contents of this column
     *
     * The data source may be specified as a query string or a key => value array
     *
     * @return array|PDOStatement|Stringable|string|null
     */
    public function getSource(): array|PDOStatement|Stringable|string|null
    {
        return isset_get_typed('array|PDOStatement|Stringable|string|null', $this->rules['source']);
    }


    /**
     * Sets a data source for the HTML client element contents of this column
     *
     * The data source may be specified as a query string or a key => value array
     *
     * @param array|PDOStatement|Stringable|string|null $value
     * @return static
     */
    public function setSource(array|PDOStatement|Stringable|string|null $value): static
    {
        return $this->setKey($value, 'source');
    }


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
    public function getVariables(): array|null
    {
        return isset_get_typed('array', $this->rules['variables']);
    }


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
    public function setVariables(array|null $value): static
    {
        return $this->setKey($value, 'variables');
    }


    /**
     * Returns a query execute bound variables execute array for the specified query string source
     *
     * @note Requires "source" to be a query string
     * @return array|null
     */
    public function getExecute(): ?array
    {
        return isset_get_typed('array', $this->rules['execute']);
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
        if (!array_key_exists('source', $this->rules)) {
            throw new OutOfBoundsException(tr('Cannot specify execute array ":value" for column ":column", a data query string source must be specified first', [
                ':column' => $this->column,
                ':value' => $value
            ]));
        }

        if (is_array($this->rules['source'])) {
            throw new OutOfBoundsException(tr('Cannot specify execute array ":value" for column ":column", the "source" must be a string query but is an array instead', [
                ':column' => $this->column,
                ':value' => $value
            ]));
        }

        return $this->setKey($value, 'execute');
    }


    /**
     * Returns the cli auto-completion queries for this column
     *
     * @return array|bool|null
     */
    public function getCliAutoComplete(): array|bool|null
    {
        return isset_get_typed('array|bool', $this->rules['cli_auto_complete']);
    }


    /**
     * Sets the cli auto-completion queries for this column
     *
     * @param array|bool|null $value
     * @return static
     */
    public function setCliAutoComplete(array|bool|null $value): static
    {
        if ($value === false) {
            throw new OutOfBoundsException(tr('Invalid value "FALSE" specified for column ":column", it must be "TRUE" or an array with only the keys "word" and "noword"', [
                ':column' => $this->column,
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
                throw new OutOfBoundsException(tr('Invalid value ":value" specified for column ":column", it must be "TRUE" or an array with only the keys "word" and "noword"', [
                    ':column' => $this->column,
                    ':value' => $value
                ]));
            }
        }

        return $this->setKey($value, 'cli_auto_complete');
    }


    /**
     * Returns the alternative CLI column names for this column
     *
     * @return string|null
     */
    public function getCliColumn(): ?string
    {
        if (PLATFORM_WEB or !$this->data_entry->isApplying()) {
            // We're either on web, or on CLI while data is not being applied but set manually. Return the HTTP column
            return $this->column;
        }

        // We're on the command line and data is being applied. We're working with data from the $argv command line
        if (empty($this->rules['cli_column'])) {
            // This column cannot be modified on the command line, no definition available
            return null;
        }

        $return = isset_get_typed('string', $this->rules['cli_column']);

        if (str_starts_with($return, '[') and str_ends_with($return, ']')) {
            // Strip the []
            $return = substr($return, 1, -1);
        }

        return $return;
    }


    /**
     * Sets the alternative CLI column names for this column
     *
     * @param string|null $value
     * @return static
     */
    public function setCliColumn(?string $value): static
    {
        return $this->setKey($value, 'cli_column');
    }


    /**
     * Returns if this column is optional or not
     *
     * @note Defaults to false
     * @return bool
     */
    public function getOptional(): bool
    {
        return isset_get_typed('bool', $this->rules['optional'], false);
    }


    /**
     * Returns if this column is required or not
     *
     * @note Is the exact opposite of Definition::getOptional()
     * @note Defaults to true
     * @return bool
     */
    public function getRequired(): bool
    {
        return !$this->getOptional();
    }


    /**
     * Sets if this column is optional or not
     *
     * @note Defaults to false
     * @param bool|null $value
     * @param mixed $initial_default
     * @return static
     */
    public function setOptional(?bool $value, mixed $initial_default = null): static
    {
        if (!$value and $initial_default) {
            // If not optional, we cannot have a default value
            throw new OutOfBoundsException(tr('Cannot assign default value ":value" when the definition is not optional', [
                ':value' => $initial_default
            ]));
        }

        $this->setKey($initial_default, 'default');
        $this->setKey((bool) $value   , 'optional');

        return $this;
    }


    /**
     * Returns the placeholder for this column
     *
     * @return string|null
     */
    public function getPlaceholder(): ?string
    {
        return isset_get_typed('string', $this->rules['placeholder']);
    }


    /**
     * Sets the placeholder for this column
     *
     * @param string|null $value
     * @return static
     */
    public function setPlaceholder(?string $value): static
    {
        $this->validateTextTypeElement('placeholder', $value);
        return $this->setKey($value, 'placeholder');
    }


    /**
     * Returns the display_callback for this column
     *
     * @return callable|null
     */
    public function getDisplayCallback(): ?callable
    {
        return isset_get_typed('object|callable', $this->rules['display_callback']);
    }


    /**
     * Sets the display_callback for this column
     *
     * @param callable|null $value
     * @return static
     */
    public function setDisplayCallback(?callable $value): static
    {
        return $this->setKey($value, 'display_callback');
    }


    /**
     * Returns the minlength for this textarea or text input column
     *
     * @return int|null
     */
    public function getMinlength(): ?int
    {
        return isset_get_typed('int', $this->rules['minlength']);
    }


    /**
     * Sets the minlength for this textarea or text input column
     *
     * @param int|null $value
     * @return static
     */
    public function setMinlength(?int $value): static
    {
        $this->validateTextTypeElement('minlength', $value);
        return $this->setKey($value, 'minlength');
    }


    /**
     * Returns the maxlength for this textarea or text ibput column
     *
     * @return int|null
     */
    public function getMaxlength(): ?int
    {
        return isset_get_typed('int', $this->rules['maxlength']);
    }


    /**
     * Sets the maxlength for this textarea or text input column
     *
     * @param int|null $value
     * @return static
     */
    public function setMaxlength(?int $value): static
    {
        return $this->setKey($value, 'maxlength');
    }


    /**
     * Returns the pattern for this textarea or text input column
     *
     * @return string|null
     */
    public function getPattern(): ?string
    {
        return isset_get_typed('string', $this->rules['pattern']);
    }


    /**
     * Sets the pattern for this textarea or text input column
     *
     * @param string|null $value
     * @return static
     */
    public function setPattern(?string $value): static
    {
        $this->validateTextTypeElement('pattern', $value);
        return $this->setKey($value, 'pattern');
    }


    /**
     * Returns the tooltip for this column
     *
     * @return string|null
     */
    public function getTooltip(): ?string
    {
        return isset_get_typed('string', $this->rules['tooltip']);
    }


    /**
     * Sets  the tooltip for this column
     *
     * @param string|null $value
     * @return static
     */
    public function setTooltip(?string $value): static
    {
        return $this->setKey($value, 'tooltip');
    }


    /**
     * Returns the minimum value for number input elements
     *
     * @return float|int|null
     */
    public function getMin(): float|int|null
    {
        return isset_get_typed('float|int', $this->rules['min']);
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
        return $this->setKey($value, 'min');
    }


    /**
     * Returns the maximum value for number input elements
     *
     * @return float|int|null
     */
    public function getMax(): float|int|null
    {
        return isset_get_typed('float|int', $this->rules['max']);
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
        return $this->setKey($value, 'max');
    }


    /**
     * Return the step value for number input elements
     *
     * @return string|float|int|null
     */
    public function getStep(): string|float|int|null
    {
        return isset_get_typed('string|float|int', $this->rules['step']);
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
        return $this->setKey($value, 'step');
    }


    /**
     * Returns the rows value for textarea elements
     *
     * @return int|null
     */
    public function getRows(): int|null
    {
        return isset_get_typed('int', $this->rules['rows']);
    }


    /**
     * Sets the rows value for textarea elements
     *
     * @param int|null $value
     * @return static
     */
    public function setRows(?int $value): static
    {
        if (isset_get($this->rules['element']) !== 'textarea') {
            throw new OutOfBoundsException(tr('Cannot define rows for column ":column", the element is a ":element" but should be a "textarea', [
                ':column'   => $this->column,
                ':element' => $value,
            ]));
        }

        return $this->setKey($value, 'rows');
    }


    /**
     * Returns the default value for this column
     *
     * @return mixed
     */
    public function getDefault(): mixed
    {
        return isset_get($this->rules['default']);
    }


    /**
     * Sets the default value for this column
     *
     * @param mixed $value
     * @return static
     */
    public function setDefault(mixed $value): static
    {
        return $this->setKey($value, 'default');
    }


    /**
     * Returns the initial default value for this column
     *
     * @return mixed
     */
    public function getInitialDefault(): mixed
    {
        return isset_get($this->rules['initial_default']);
    }


    /**
     * Sets the initial default value for this column
     *
     * @param mixed $value
     * @return static
     */
    public function setInitialDefault(mixed $value): static
    {
        return $this->setKey($value, 'initial_default');
    }


    /**
     * Returns if this column should be stored with NULL in the database if empty
     *
     * @note Defaults to false
     * @return bool
     */
    public function getNullDb(): bool
    {
        return isset_get_typed('bool', $this->rules['null_db'], true);
    }


    /**
     * Sets if this column should be stored with NULL in the database if empty
     *
     * @note Defaults to false
     * @param bool $value
     * @param string|float|int|null $default
     * @return static
     */
    public function setNullDb(bool $value, string|float|int|null $default = null): static
    {
        $this->setKey($value, 'null_db');
        $this->setKey($default, 'default');

        return $this;
    }


    /**
     * Returns if this column should be disabled if the value is NULL
     *
     * @note Defaults to false
     * @return bool
     */
    public function getNullDisabled(): bool
    {
        return isset_get_typed('bool', $this->rules['null_disabled'], false);
    }


    /**
     * Sets if this column should be disabled if the value is NULL
     *
     * @note Defaults to false
     * @param bool|null $value
     * @return static
     */
    public function setNullDisabled(?bool $value): static
    {
        return $this->setKey((bool) $value, 'null_disabled');
    }


    /**
     * Returns if this column should be readonly if the value is NULL
     *
     * @note Defaults to false
     * @return bool
     */
    public function getNullReadonly(): bool
    {
        return isset_get_typed('bool', $this->rules['null_readonly'], false);
    }


    /**
     * Sets if this column should be readonly if the value is NULL
     *
     * @note Defaults to false
     * @param bool|null $value
     * @return static
     */
    public function setNullReadonly(?bool $value): static
    {
        return $this->setKey((bool) $value, 'null_readonly');
    }


    /**
     * Returns the type for this element if the value is NULL
     *
     * @return string|null
     */
    public function getNullType(): ?string
    {
        return isset_get_typed('string', $this->rules['null_type']);
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
        return $this->setKey($value->value, 'type');
    }


    /**
     * Returns the type for this element if the value is NULL
     *
     * @return array|null
     */
    public function getValidationFunctions(): ?array
    {
        return isset_get_typed('array', $this->rules['validation_functions']);
    }


    /**
     * Clears all currently existing validation functions for this definition
     *
     * @return static
     */
    public function clearValidationFunctions(): static
    {
        $this->validations = [];
        return $this;
    }


    /**
     * Adds the specified validation function to the validation functions list for this definition
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
     * Returns the help text for this column
     *
     * @return string|null
     */
    public function getHelpText(): ?string
    {
        return isset_get_typed('string', $this->rules['help_text']);
    }


    /**
     * Sets the help text for this column
     *
     * @param string|null $value
     * @return static
     */
    public function setHelpText(?string $value): static
    {
        $this->setKey(trim($value), 'help_text');

        if (!$this->getKey('tooltip')) {
            // Default tooltip to help text
            return $this->setTooltip($value);
        }

        return $this;
    }


    /**
     * Returns the help text group for this column
     *
     * @return string|null
     */
    public function getHelpGroup(): ?string
    {
        return isset_get_typed('string', $this->rules['help_group']);
    }


    /**
     * Sets the help text group for this column
     *
     * @param string|null $value
     * @return static
     */
    public function setHelpGroup(?string $value): static
    {
        return $this->setKey($value, 'help_group');
    }


    /**
     * Returns true if the specified input type is supported
     *
     * @param string $type
     * @return bool
     */
    public function inputTypeSupported(string $type): bool
    {
        return in_array($type, static::$supported_input_types);
    }


    /**
     * Validate this column according to the column definitions
     *
     * @param ValidatorInterface $validator
     * @param string|null $prefix
     * @return bool
     */
    public function validate(ValidatorInterface $validator, ?string $prefix): bool
    {
        if ($this->isMeta()) {
            // This column is metadata and should not be modified or validated, plain ignore it.
            return false;
        }

        if ($this->getReadonly() or $this->getDisabled()) {
            // This column cannot be modified and should not be validated, unless its new or has a static value
            if (!$this->data_entry->isNew() and !$this->getValue()) {
                return false;
            }
        }

        // Checkbox inputs always are boolean and does this column have a prefix?
        $bool   = ($this->getType() === 'checkbox');
        $column = $this->getCliColumn();

        if (!$column) {
            // This column name is empty. Coming from static::getCliColumn() this means that this column should NOT be
            // validated
            return false;
        }

        // Column name prefix is an HTML form array prefix? Then close the array
        if (str_ends_with((string) $prefix, '[')) {
            $column .= ']';
        }

        if ($this->getValue()) {
            // This column has a static value, force the value
            $value = $this->getValue();

            if (is_callable($this->getValue())) {
                $value = $this->getValue()($validator->getSource(), $prefix);
            }

            $validator->set($value, $prefix . $column);
        }

        // Set the column prefix and select the column
        $validator
            ->setColumnPrefix($prefix)
            ->select($column, !$bool);

        // Apply default validations
        if ($this->getOptional()) {
            $validator->isOptional($this->getDefault());
        }

        if ($bool) {
            $validator->isBoolean();

        } else {
            switch ($this->getElement()) {
                case 'textarea':
                    $validator->sanitizeTrim();

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
                            $validator->sanitizeTrim();
                            $validator->isDate();
                            break;

                        case 'color':
                            $validator->sanitizeTrim();
                            $validator->isColor();
                            break;

                        case 'tel':
                            $validator->sanitizeTrim();
                            $validator->isPhoneNumber();
                            break;

                        case 'email':
                            $validator->sanitizeTrim();
                            $validator->isEmail();
                            break;

                        case 'time':
                            $validator->sanitizeTrim();
                            $validator->isTime();
                            break;

                        case 'datetime-local':
                            $validator->sanitizeTrim();
                            $validator->isDateTime();
                            break;

                        case 'number':
                            // no break
                        case 'year':
                            // no break
                        case 'month':
                            // no break
                        case 'week':
                            // no break
                        case 'day':
                            // Validate numbers
                            if ($this->getMin()) {
                                $validator->isMoreThan($this->getMin(), true);
                            }

                            if ($this->getMax()) {
                                $validator->isLessThan($this->getMax(), true);
                            }

                            break;

                        case 'array_json':
                            $validator->sanitizeForceArray(',');
                            break;

                        default:
                            // Validate input text strings
                            $validator->sanitizeTrim();

                            if ($this->getMinlength()) {
                                $validator->hasMinCharacters($this->getMinlength());
                            }

                            if ($this->getMaxlength()) {
                                $validator->hasMaxCharacters($this->getMaxlength());
                            }
                    }

                    break;

                case 'select':
                    $validator->sanitizeTrim();
            }

            $source = $this->getSource();

            if ($source) {
                if ($source instanceof SqlQueryInterface) {
                    $source = sql()->query($source);

                } elseif ($source instanceof PDOStatement) {
                    $source = $source->fetchAll();

                } elseif (is_array($source)) {
                    // The data value must be in the definition source
                    $validator->isInArray(array_keys($source));

                } elseif (is_string($source)) {
                    throw new OutOfBoundsException(tr('Invalid source specified for DataEntry Definition ":column"', [
                        ':column' => $this->column
                    ]));
                }

            }
        }

        // Apply all other validations
        foreach ($this->validations as $validation) {
            $validation($validator);
        }

        return true;
    }


    /**
     * Ensures that the current column uses a text type input element or textarea element
     *
     * @param string $key
     * @param string|float|int|null $value
     * @return void
     */
    protected function validateTextTypeElement(string $key, string|float|int|null $value): void
    {
        if (is_callable(isset_get($this->rules['element']))) {
            // We can't validate data types for this since it's a callback function
            return;
        }

        switch (isset_get($this->rules['element'])) {
            case 'textarea':
                // no break
            case 'select':
                // no break
            case 'div':
                // no break
            case 'span':
                // no break
            case 'tooltip': // This is a pseudo-element
                break;

            case null:
                // This is the default, so "input"
            case 'input':
                if (!array_key_exists('type', $this->rules) or in_array($this->rules['type'], ['text', 'email', 'url', 'password'])) {
                    break;
                }

                throw new OutOfBoundsException(tr('Cannot set :attribute ":value" for column ":column", it is an ":type" type input element, :attribute can only be used for textarea elements or input elements with "text" type', [
                    ':attribute' => $key,
                    ':column'     => $this->column,
                    ':type'      => $this->rules['type'] ?? 'text',
                      ':value'     => $value
                ]));

            default:
                throw new OutOfBoundsException(tr('Cannot set :attribute ":value" for column ":column", it is an ":element" element, :attribute can only be used for textarea elements or input elements with "text" type', [
                    ':attribute' => $key,
                    ':column'     => $this->column,
                    ':element'   => $this->rules['element'],
                    ':value'     => $value
                ]));
        }
    }


    /**
     * Ensures that the current column uses a number type input element
     *
     * @note This method considers number the following input types: number, range, date, datetime-local, time, week,
     *       month
     * @param string $key
     * @param string|float|int $value
     * @return void
     */
    protected function validateNumberTypeInput(string $key, string|float|int $value): void
    {
        if (is_callable(isset_get($this->rules['element']))) {
            // We can't validate data types for this since it's a callback function
            return;
        }

        if (isset_get($this->rules['element'], 'input') !== 'input') {
            throw new OutOfBoundsException(tr('Cannot set :attribute ":value" for column ":column", it is an ":element" element, :attribute can only be used for "number" type input elements', [
                ':attribute' => $key,
                ':column'     => $this->column,
                ':element'   => $this->rules['element'],
                ':value'     => $value
            ]));
        }

        switch (isset_get($this->rules['type'], 'text')) {
            case 'number':
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
                throw new OutOfBoundsException(tr('Cannot set :attribute ":value" for column ":column", it is an ":type" type input element, :attribute can only be used for "number" type input elements', [
                    ':attribute' => $key,
                    ':column'     => $this->column,
                    ':type'      => $this->rules['type'] ?? 'text',
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

        if (empty($this->rules['element'])) {
            $this->rules['element'] = 'input';
        }

        if ($this->rules['element'] !== 'input') {
            throw new OutOfBoundsException(tr('Cannot set :key ":value" for column ":column" as the element must be input (or empty, default) but is ":element"', [
                ':key'     => $key,
                ':value'   => $value,
                ':column'   => $this->column,
                ':element' => $this->rules['element']
            ]));
        }

        if (!$this->inputTypeSupported($value)) {
            throw new OutOfBoundsException(tr('Cannot set ":key" ":value" for column ":column", only the types ":types" are supported', [
                ':key'   => $key,
                ':value' => $value,
                ':column' => $this->column,
                ':types' => static::$supported_input_types
            ]));
        }
    }
}
