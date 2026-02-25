<?php

/**
 * Class DataEntryForm
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Forms;

use PDOStatement;
use Phoundation\Core\Libraries\Library;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Traits\TraitDataCacheKey;
use Phoundation\Data\Traits\TraitDataDataEntry;
use Phoundation\Data\Traits\TraitDataDefinitions;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Phoundation\Web\Exception\WebRenderException;
use Phoundation\Web\Html\Components\ElementsBlock;
use Phoundation\Web\Html\Components\Forms\Exception\FormsException;
use Phoundation\Web\Html\Components\Forms\Interfaces\DataEntryFormInterface;
use Phoundation\Web\Html\Components\Forms\Interfaces\DataEntryFormRowsInterface;
use Phoundation\Web\Html\Components\Input\Buttons\AuditButton;
use Phoundation\Web\Html\Components\Input\Buttons\BackButton;
use Phoundation\Web\Html\Components\Input\Buttons\Button;
use Phoundation\Web\Html\Components\Input\Buttons\CreateButton;
use Phoundation\Web\Html\Components\Input\Buttons\DeleteButton;
use Phoundation\Web\Html\Components\Input\Buttons\LockButton;
use Phoundation\Web\Html\Components\Input\Buttons\SaveButton;
use Phoundation\Web\Html\Components\Input\Buttons\UndeleteButton;
use Phoundation\Web\Html\Components\Input\Buttons\UnlockButton;
use Phoundation\Web\Html\Components\Input\InputHidden;
use Phoundation\Web\Html\Components\Interfaces\RenderInterface;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumElement;
use Phoundation\Web\Html\Enums\EnumInputType;
use Phoundation\Web\Requests\Request;
use Stringable;
use Throwable;


class DataEntryForm extends ElementsBlock implements DataEntryFormInterface
{
    use TraitDataCacheKey;
    use TraitDataDataEntry;
    use TraitDataDefinitions;


    /**
     * Counter for list forms
     *
     * @var int $list_count
     */
    protected static int $list_count = 0;

    /**
     * Optional class for input elements
     *
     * @var string $input_class
     */
    protected string $input_class;

    /**
     * If set, the screen focus will automatically go to the specified element
     *
     * @var string|null $auto_focus_id
     */
    protected ?string $auto_focus_id = null;

    /**
     * The DataEntryForm rows renderer
     *
     * @var DataEntryFormRowsInterface $_rows
     */
    protected DataEntryFormRowsInterface $_rows;


    /**
     * DataEntryForm class constructor
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     */
    public function __construct(IteratorInterface|PDOStatement|array|string|null $source = null)
    {
        parent::__construct($source);

        $this->_rows = new DataEntryFormRows($this);
    }


    /**
     * Sets if meta-information is visible at all, or not
     *
     * @param bool $meta_visible
     *
     * @return static
     */
    public function setRenderMeta(bool $meta_visible): static
    {
        $this->_definitions->setRenderMeta($meta_visible);
        return $this;
    }


    /**
     * Returns the optional class for input elements
     *
     * @return string
     */
    public function getInputClass(): string
    {
        return $this->input_class;
    }


    /**
     * Sets the optional class for input elements
     *
     * @param string $input_class
     *
     * @return static
     */
    public function setInputClass(string $input_class): static
    {
        $this->input_class = $input_class;
        return $this;
    }


    /**
     * Returns the cache key for this DataEntryForm object
     *
     * @return string|null
     */
    public function getCacheKeySeed(): ?string
    {
        return PROJECT . '#DataEntryForm#' . static::class . '#' . Json::encode(['render', Request::getUrl(), $this->getName(), $this->getId(), $this->_data_entry?->getIdentifier()], force_single_line: true);
    }


    /**
     * Renders and returns the DataEntry as an HTML web form
     *
     * @return string|null
     */
    public function render(): ?string
    {
        return cache('html')->getOrGenerate($this->getCacheKey(), function () {
            if (!$this->getDefinitionsObject()) {
                if ($this->render_contents_only) {
                    return $this->content;
                }

                throw new OutOfBoundsException(tr('Cannot render DataEntryForm for class ":class", no column definitions specified. Either specify definitions, or set render_contents_only', [
                    ':class' => isset($this->_data_entry) ? get_class($this->_data_entry) : null,
                ]));
            }

            $source        = $this->getSource();
            $_definitions = $this->getDefinitionsObject();
            $prefix        = $this->getDefinitionsObject()->getPrefix();
            $auto_focus_id = $this->getAutofocusId();

            if ($prefix) {
                if (str_ends_with($prefix, '[]')) {
                    // This is an array prefix with the closing tag attached, remove the closing tag
                    $prefix = substr($prefix, 0, -1);
                }

                if (str_contains($prefix, '[]')) {
                    // This prefix contains a [] to indicate a list item. Specify the correct ID's
                    $prefix = str_replace('[]', '[' . static::$list_count . ']', $prefix);
                }

                $is_array = str_ends_with((string) $prefix, '[');

            } else {
                $is_array = false;
            }

            /*
             * $data column keys: (Or just use Definitions class)
             *
             * FIELD          DATATYPE           DEFAULT VALUE  DESCRIPTION
             * value          mixed              null           The value for this entry
             * visible        boolean            true           If false, this key will not be shown on web, and be readonly
             * virtual        boolean            false          If true, this key will be visible and can be modified but it
             *                                                  will not exist in database. It instead will be used to generate
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
             */
            // If form key definitions are available, reorder the keys as in the form key definitions
            // Go over each key and add it to the form
            foreach ($_definitions as $column => $_definition) {
                try {
                    // Add column name prefix
                    $field_name = $prefix . $column;

                    if ($field_name === $auto_focus_id) {
                        // This column has autofocus
                        $_definition->setAutoFocus(true);
                    }

                    if ($is_array) {
                        // The column name prefix is an HTML form array prefix, close that array
                        $field_name .= ']';
                    }

                    if (!is_object($_definition) or !($_definition instanceof DefinitionInterface)) {
                        throw new OutOfBoundsException(tr('Data key definition for column ":column / :field_name" is invalid. Iit should be an array or Definition type  but contains ":data"', [
                            ':column'     => $column,
                            ':field_name' => $field_name,
                            ':data'       => gettype($_definition) . ': ' . $_definition,
                        ]));
                    }

                    if ($_definition->getPreRenderFunctions()) {
                        $source[$column] = $this->executePreRenderFunctions($_definition, $source, array_get_safe($source, $column));
                    }

                    if ($_definition->isMeta()) {
                        // This is an immutable meta-column, virtual column, or readonly column.
                        // In creation mode we are not even going to show this, in edit mode do not put a column name because
                        // users  are not even supposed to be able to submit this
                        if (empty($source['id'])) {
                            continue;
                        }

                        if (!$_definitions->getRenderMeta()) {
                            continue;
                        }

                        $field_name = null;
                        $_definition->setPrefix(null)->setColumn(null);
                    }

                    if (is_callable($_definition->getRender(false))) {
                        // Rendering depends on the return of the callback
                        $_definition->setRender($_definition->getRender(false)());
                    }

                    if (!$_definition->getRender()) {
                        // This element should not be shown, continue
                        continue;
                    }

                    // Either this component or the entire form being readonly or disabled will make the component the same
                    $_definition->setReadonly($_definition->getReadonly() or $this->getReadonly());
                    $_definition->setDisabled($_definition->getDisabled() or $this->getDisabled());

                    if ($_definition->getDisabled() or $_definition->getReadonly()) {
                        // This is an immutable column. Do not add a column names as users  are not supposed to submit this.
                        $field_name = '';
                        $_definition->setPrefix(null)->setColumn(null);
                    }

                    // Ensure security column values are never sent in the form
                    switch ($column) {
                        case 'password':
                            $source[$column] = '';
                    }

                    $execute = $_definition->getExecute();

                    if (is_string($execute)) {
                        // Build the source execute array from the specified column
                        $items = explode(',', $execute);
                        $execute = [];

                        foreach ($items as $item) {
                            $execute[':' . $item] = array_get_safe($source, $item);
                        }
                    }

                    // Select default element
                    if (!$_definition->getElement()) {
                        if ($_definition->getSource()) {
                            // Default element for form items with a source is "select"
                            // TODO CHECK THIS! WHAT IF SOURCE IS A SINGLE STRING?
                            $_definition->setElement(EnumElement::select);

                        } else {
                            // Default element for form items "text input"
                            $_definition->setElement(EnumElement::input);
                        }
                    }

                    if ($_definition->getDisplayCallback()) {
                        // Execute the specified callback on the data before displaying it
                        $source[$column] = $_definition->getDisplayCallback()(array_get_safe($source, $column), $source);
                    }

                    // Set default value and override key entry values if value is null
                    if (array_get_safe($source, $column) === null) {
                        if ($_definition->getNullElement()) {
                            $_definition->setElement($_definition->getNullElement());
                        }

                        if ($_definition->getNullInputType()) {
                            $_definition->setInputType($_definition->getNullInputType());
                        }

                        if ($_definition->getNullDisabled()) {
                            $_definition->setDisabled($_definition->getNullDisabled());
                        }

                        if ($_definition->getNullReadonly()) {
                            $_definition->setReadonly($_definition->getNullReadonly());
                        }

                        $source[$column] = $_definition->getNullDefault();
                    }

                    // Set value to value specified in $data
                    if ($_definition->getValue()) {
                        $source[$column] = $_definition->getValue();

                        // The specified value is an anonymous function, execute it to get the value out of it
                        if (is_callable($source[$column])) {
                            $source[$column] = $source[$column]();
                        }

                        // Apply variables
                        foreach ($source as $source_key => $source_value) {
                            if ($_definitions->keyExists($source_key)) {
                                if (str_contains((string)$source[$column], ':' . $source_key)) {
                                    $source[$column] = str_replace(':' . $source_key, (string)$source_value, (string)$source[$column]);
                                }
                            }
                        }
                    }

                    // Build the form elements unless a component or content was specified manually
                    if (!$_definition->getOutput()) {
                        switch ($_definition->getElement()) {
                            case EnumElement::input:
                                if (!$_definition->getInputType()) {
                                    throw new OutOfBoundsException(tr('No input type specified for column ":column / :field_name"', [
                                        ':field_name' => $field_name,
                                        ':column'     => $column,
                                    ]));
                                }

                                // If we have a source query specified, then get the actual value from the query
                                if ($_definition->getSource()) {
                                    if (!is_array($_definition->getSource())) {
                                        if (!is_string($_definition->getSource())) {
                                            if ($_definition->getSource() instanceof Stringable) {
                                                // This is a Stringable object
                                                $_definition->setSource((string)$_definition->getSource());

                                            } else {
                                                // The Only possibility left is instanceof PDOStatement
                                                $_definition->setSource(sql()->getColumn($_definition->getSource(), $execute));
                                            }
                                        }
                                    }
                                }

                                // Build the element class path and load the required class file
                                $type = match ($_definition->getInputType()) {
                                    EnumInputType::datetime_local => 'DateTimeLocal',
                                    EnumInputType::auto_suggest   => 'AutoSuggest',
                                    default                       => str_replace(' ', '', Strings::camelCase(str_replace([' ', '-', '_',], ' ', $_definition->getInputType()->value))),
                                };

                                // TODO Replace $field_name with the name and prefix from the Definition object
                                // Get the class for this element and ensure the library file is loaded
                                // Build the component, depending on the input type
                                $element_class = Library::includeClassFile('\\Phoundation\\Web\\Html\\Components\\Input\\Input' . $type);
                                $_component   = match ($_definition->getInputType()) {
                                    EnumInputType::number          => $element_class::new()
                                                                                    ->setDefinitionObject($_definition)
                                                                                    ->setMin($_definition->getMin())
                                                                                    ->setMax($_definition->getMax())
                                                                                    ->setStep($_definition->getStep())
                                                                                    ->setName($field_name)
                                                                                    ->setValue($source[$column]),

//                                    EnumInputType::datetime_local,
                                    EnumInputType::date            => $element_class::new()
                                                                                    ->setDefinitionObject($_definition)
                                                                                    ->setMin($_definition->getMin())
                                                                                    ->setMax($_definition->getMax())
                                                                                    ->setName($field_name)
                                                                                    ->setValue($source[$column]),

                                    EnumInputType::auto_suggest    => $element_class::new()
                                                                                    ->setDefinitionObject($_definition)
                                                                                    ->setAutoComplete(false)
                                                                                    ->setMinLength($_definition->getMinLength())
                                                                                    ->setMaxLength($_definition->getMaxLength())
                                                                                    ->setSourceUrl($_definition->getSource())
                                                                                    ->setVariables($_definition->getVariables())
                                                                                    ->setName($field_name)
                                                                                    ->setValue($source[$column]),

                                    EnumInputType::select          => $element_class::new()
                                                                                    ->setDefinitionObject($_definition)
                                                                                    ->setName($field_name)
                                                                                    ->setValue($source[$column]),

                                    EnumInputType::checkbox        => $element_class::new()
                                                                                    ->setDefinitionObject($_definition)
                                                                                    ->setName($field_name)
                                                                                    ->setValue('1')
                                                                                    ->setChecked((bool)$source[$column]),

                                    EnumInputType::delete_button   => DeleteButton::new()
                                                                                  ->setDefinitionObject($_definition)
                                                                                  ->setHidden($_definition->getHidden())
                                                                                  ->setValue($_definition->getValue())
                                                                                  ->setContent($_definition->getContent()),

                                    EnumInputType::save_button     => SaveButton::new()
                                                                                ->setDefinitionObject($_definition)
                                                                                ->setHidden($_definition->getHidden())
                                                                                ->setValue($_definition->getValue())
                                                                                ->setContent($_definition->getContent()),

                                    EnumInputType::back_button     => BackButton::new()
                                                                                ->setDefinitionObject($_definition)
                                                                                ->setHidden($_definition->getHidden())
                                                                                ->setValue($_definition->getValue())
                                                                                ->setContent($_definition->getContent()),

                                    EnumInputType::undelete_button => UndeleteButton::new()
                                                                                    ->setDefinitionObject($_definition)
                                                                                    ->setHidden($_definition->getHidden())
                                                                                    ->setValue($_definition->getValue())
                                                                                    ->setContent($_definition->getContent()),

                                    EnumInputType::lock_button     => LockButton::new()
                                                                                ->setDefinitionObject($_definition)
                                                                                ->setHidden($_definition->getHidden())
                                                                                ->setValue($_definition->getValue())
                                                                                ->setContent($_definition->getContent()),

                                    EnumInputType::unlock_button   => UnlockButton::new()
                                                                                ->setDefinitionObject($_definition)
                                                                                ->setHidden($_definition->getHidden())
                                                                                ->setValue($_definition->getValue())
                                                                                ->setContent($_definition->getContent()),

                                    EnumInputType::create_button   => CreateButton::new()
                                                                                  ->setDefinitionObject($_definition)
                                                                                  ->setHidden($_definition->getHidden())
                                                                                  ->setValue($_definition->getValue())
                                                                                  ->setContent($_definition->getContent()),

                                    EnumInputType::audit_button    => AuditButton::new()
                                                                                  ->setDefinitionObject($_definition)
                                                                                  ->setHidden($_definition->getHidden())
                                                                                  ->setValue($_definition->getValue())
                                                                                  ->setContent($_definition->getContent()),

                                    EnumInputType::reset,
                                    EnumInputType::button,
                                    EnumInputType::submit          => Button::new()
                                                                            ->setDefinitionObject($_definition)
                                                                            ->setHidden($_definition->getHidden())
                                                                            ->setValue($_definition->getValue())
                                                                            ->setContent($_definition->getContent()),

                                    // TODO This should be using ->setDefinitionObject($_definition)!
                                    EnumInputType::hidden          => $element_class::new()
                                                                                    ->setRequired($_definition->getRequired())
                                                                                    ->setName($field_name)
                                                                                    ->setValue($source[$column]),

                                    default                        => $element_class::new()
                                                                                    ->setDefinitionObject($_definition)
                                                                                    ->setMinLength($_definition->getMinLength())
                                                                                    ->setMaxLength($_definition->getMaxLength())
                                                                                    ->setAutoComplete($_definition->getAutoComplete())
                                                                                    ->setAutoSubmit($_definition->getAutoSubmit())
                                                                                    ->setName($field_name)
                                                                                    ->setValue($source[$column])
                                };

                                $this->_rows->add($_definition, $_component);
                                break;

                            case EnumElement::textarea:
                                // If we have a source query specified, then get the actual value from the query
                                if ($_definition->getSource()) {
                                    $source[$column] = sql()->getColumn($_definition->getSource(), $execute);
                                }

                                // Get the class for this element and ensure the library file is loaded
                                $element_class = Library::includeClassFile('\\Phoundation\\Web\\Html\\Components\\Input\\InputTextArea');
                                $_component   = $element_class::new()
                                                               ->setDefinitionObject($_definition)
                                                               ->setAutoComplete($_definition->getAutoComplete())
                                                               ->setAutoSubmit($_definition->getAutoSubmit())
                                                               ->setHidden($_definition->getHidden())
                                                               ->setMaxLength($_definition->getMaxLength())
                                                               ->setRows($_definition->getRows())
                                                               ->setName($field_name)
                                                               ->setContent(array_get_safe($source, $column));

                                $this->_rows->add($_definition, $_component);
                                break;

                            case EnumElement::div:
                                // no break;

                            case EnumElement::span:
                                // no break;

                            case EnumElement::label:
                                $element_class = Strings::capitalize($_definition->getElement()->value);

                                // If we have a source query specified, then get the actual value from the query
                                if ($_definition->getSource()) {
                                    $source[$column] = sql()->getColumn($_definition->getSource(), $execute);
                                }

                                // Get the class for this element and ensure the library file is loaded
                                $element_class = Library::includeClassFile('\\Phoundation\\Web\\Html\\Components\\' . $element_class);
                                $_component   = $element_class::new()
                                                               ->setDefinitionObject($_definition)
                                                               ->setName($field_name)
                                                               ->setContent(array_get_safe($source, $column));

                                $this->_rows->add($_definition, $_component);
                                break;

                            case EnumElement::button:
                                $element_class = Strings::capitalize($_definition->getElement()->value);
                                $element_class = Library::includeClassFile('\\Phoundation\\Web\\Html\\Components\\Input\\Buttons\\' . $element_class);
                                $_component   = $element_class::new()
                                                               ->setDefinitionObject($_definition)
                                                               ->setName($field_name)
                                                               ->setContent($source[$column]);
                                $this->_rows->add($_definition, $_component);
                                break;

                            case EnumElement::select:
                                // Get the class for this element and ensure the library file is loaded
                                $element_class = Library::includeClassFile('\\Phoundation\\Web\\Html\\Components\\Input\\InputSelect');
                                $_component   = $element_class::new()
                                                               ->setDefinitionObject($_definition)
                                                               ->setSource($_definition->getSource(), $execute)
                                                               ->setDisabled($_definition->getDisabled() or $_definition->getReadonly())
                                                               ->setReadOnly((bool)$_definition->getReadonly())
                                                               ->setHidden($_definition->getHidden())
                                                               ->setName($field_name)
                                                               ->setAutoComplete($_definition->getAutoComplete())
                                                               ->setAutoSubmit($_definition->getAutoSubmit())
                                                               ->setSelected(array_get_safe($source, $column))
                                                               ->setAutoFocus($_definition->getAutoFocus());

                                $this->_rows->add($_definition, $_component);
                                break;

                            case EnumElement::inputmultibuttontext:
                                // Get the class for this element and ensure the library file is loaded
                                $element_class = Library::includeClassFile('\\Phoundation\\Web\\Html\\Components\\Input\\InputMultiButtonText');
                                $input = $element_class::new()
                                                       ->setSource($_definition->getSource());

                                $input->getButton()
                                      ->setMode(EnumDisplayMode::from($_definition->getMode()))
                                      ->setContent($_definition->getLabel());

                                $_component = $input->getInput()
                                                     ->setDefinitionObject($_definition)
                                                     ->setHidden($_definition->getHidden())
                                                     ->setName($field_name)
                                                     ->setValue($source[$column])
                                                     ->setContent(array_get_safe($source, $column))
                                                     ->setAutoFocus($_definition->getAutoFocus());

                                $this->_rows->add($_definition, $_component);
                                break;

                            case EnumElement::hr:
                                // Get the class for this element and ensure the library file is loaded
                                $element_class = Library::includeClassFile('\\Phoundation\\Web\\Html\\Components\\Hr');
                                $_component   = $element_class::new();

                                $this->_rows->add($_definition, $_component);
                                break;

                            case null:
                                throw new OutOfBoundsException(tr('No element specified for key ":key"', [
                                    ':key' => $column,
                                ]));

                            default:
                                if (!$_definition->getElement()) {
                                    throw new OutOfBoundsException(tr('No element specified for key ":key"', [
                                        ':key' => $column,
                                    ]));
                                }

                                throw new OutOfBoundsException(tr('Unknown or unsupported element ":element" specified for key ":key"', [
                                    ':element' => $_definition->getElement()->value,
                                    ':key'     => $column,
                                ]));
                        }

                    } elseif ($_definition->getOutput()) {
                        if (is_callable($_definition->getOutput())) {
                            // Component will be generated in a callback
                            if ($_definition->getHidden()) {
                                $this->_rows->add($_definition, InputHidden::new()
                                                                             ->setName($field_name)
                                                                             ->setValue(Strings::force($source[$column], ' - ')));
                            } else {
                                $_component = $_definition->getOutput()($_definition, $column, $field_name, $source);

                                if ($_component) {
                                    if (!is_string($_component)) {
                                        if (!$_component instanceof RenderInterface) {
                                            // The content function did NOT return a render object
                                            throw new WebRenderException(tr('Failed to render DataEntryForm ":class", the column ":column" setContent method should return a RenderInterface object but returns a ":type" instead', [
                                                ':class'  => get_class($this->_data_entry),
                                                ':column' => $column,
                                                ':type'   => get_class_or_datatype($_component),
                                            ]));
                                        }

                                        $_component->setDefinitionObject($_definition);
                                    }

                                    $this->_rows->add($_definition, $_component);
                                }
                            }

                        } else {
                            // Component has been defined directly
                            $this->_rows->add($_definition, $_definition->getOutput());
                        }

//                    } elseif (is_callable($_definition->getContent())) {
//                        // Content has been specified with a callback
//                        if ($_definition->getHidden()) {
//                            $this->_rows->add($_definition, InputHidden::new()
//                                                                         ->setName($field_name)
//                                                                         ->setValue(Strings::force($source[$column], ' - ')));
//
//                        } else {
//                            $_component = $_definition->getContent()($_definition, $column, $field_name, $source);
//
//                            if ($_component) {
//                                if (!$_component instanceof RenderInterface) {
//                                    // The content function did NOT return a render object
//                                    throw new WebRenderException(tr('Failed to render DataEntryForm ":class", the column ":column" setContent method should return a RenderInterface object but returns a ":type" instead', [
//                                        ':class'  => get_class($this->_data_entry),
//                                        ':column' => $column,
//                                        ':type'   => get_class_or_datatype($_component),
//                                    ]));
//                                }
//
//                                $this->_rows->add($_definition, $_component);
//                            }
//                        }

                    } else {
                        // Content has already been rendered, display it
                        $this->_rows->add($_definition, $_definition->getOutput());
                    }

                } catch (Throwable $e) {

                    if (empty($this->_data_entry)) {
                        throw new FormsException(tr('Failed to render DataEntryForm column ":column"', [
                            ':column' => $column,
                        ]), $e);
                    }

                    throw new FormsException(tr('Failed to render DataEntryForm column ":column" for class ":class"', [
                        ':column' => $column,
                        ':class'  => get_class($this->_data_entry),
                    ]), $e);
                }
            }

            // Add one empty element to (if required) close any rows
            static::$list_count++;

            // Add the data entry object name in the ID field
            // TODO Should we always do this?
            if (empty($this->_data_entry)) {
                $return = '<div>' . $this->_rows->render() . '</div>';

            } else {
                $return = '<div id="' . $this->_data_entry->getObjectName() . ($this->_data_entry->getId(false) ? '_' . $this->_data_entry->getId(false) : null) . '" class="' . $this->_data_entry->getObjectName() . '">
                              ' .$this->_rows->render() . '
                           </div>';
            }

            // Add an optional HTML form
            if ($this->form) {
                $return = $this->form->setContent($return)->render();
            }

            return $return;
        });
    }


    /**
     * Applies pre-render functions if defined and adds the specified component to the DataEntryFormRow
     *
     * @param DefinitionInterface $_definition
     * @param array               $source
     * @param mixed               $value
     *
     * @return mixed
     */
    protected function executePreRenderFunctions(DefinitionInterface $_definition, array $source, mixed $value): mixed
    {
        // Execute all available pre-render functions
        foreach ($_definition->getPreRenderFunctions() as $function) {
            $value = $function ($_definition, $source, $value);
        }

        return $value;
    }


    /**
     * Returns the element that will receive autofocus
     *
     * @return string|null
     */
    public function getAutoFocusId(): ?string
    {
        return $this->auto_focus_id;
    }


    /**
     * Sets the element that will receive autofocus
     *
     * @param string|null $auto_focus_id
     *
     * @return static
     */
    public function setAutoFocusId(?string $auto_focus_id): static
    {
        $this->auto_focus_id = $auto_focus_id;

        return $this;
    }


    /**
     * Returns if meta-information is visible at all, or not
     *
     * @return bool
     */
    public function getRenderMeta(): bool
    {
        return $this->_definitions->getRenderMeta();
    }
}
