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
     * @var DataEntryFormRowsInterface $o_rows
     */
    protected DataEntryFormRowsInterface $o_rows;


    /**
     * DataEntryForm class constructor
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     */
    public function __construct(IteratorInterface|PDOStatement|array|string|null $source = null)
    {
        parent::__construct($source);

        $this->o_rows = new DataEntryFormRows($this);
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
        $this->o_definitions->setRenderMeta($meta_visible);
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
        return PROJECT . '#DataEntryForm#' . static::class . '#' . Json::encode(['render', Request::getUrl(), $this->getName(), $this->getId(), $this->o_data_entry?->getIdentifier()], force_single_line: true);
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
                    ':class' => isset($this->o_data_entry) ? get_class($this->o_data_entry) : null,
                ]));
            }

            $source        = $this->getSource();
            $o_definitions = $this->getDefinitionsObject();
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
             */
            // If form key definitions are available, reorder the keys as in the form key definitions
            // Go over each key and add it to the form
            foreach ($o_definitions as $column => $o_definition) {
                try {
                    // Add column name prefix
                    $field_name = $prefix . $column;

                    if ($field_name === $auto_focus_id) {
                        // This column has autofocus
                        $o_definition->setAutoFocus(true);
                    }

                    if ($is_array) {
                        // The column name prefix is an HTML form array prefix, close that array
                        $field_name .= ']';
                    }

                    if (!is_object($o_definition) or !($o_definition instanceof DefinitionInterface)) {
                        throw new OutOfBoundsException(tr('Data key definition for column ":column / :field_name" is invalid. Iit should be an array or Definition type  but contains ":data"', [
                            ':column'     => $column,
                            ':field_name' => $field_name,
                            ':data'       => gettype($o_definition) . ': ' . $o_definition,
                        ]));
                    }

                    if ($o_definition->getPreRenderFunctions()) {
                        $source[$column] = $this->executePreRenderFunctions($o_definition, $source, array_get_safe($source, $column));
                    }

                    if ($o_definition->isMeta()) {
                        // This is an immutable meta-column, virtual column, or readonly column.
                        // In creation mode we are not even going to show this, in edit mode do not put a column name because
                        // users  are not even supposed to be able to submit this
                        if (empty($source['id'])) {
                            continue;
                        }

                        if (!$o_definitions->getRenderMeta()) {
                            continue;
                        }

                        $field_name = null;
                        $o_definition->setPrefix(null)->setColumn(null);
                    }

                    if (is_callable($o_definition->getRender(false))) {
                        // Rendering depends on the return of the callback
                        $o_definition->setRender($o_definition->getRender(false)());
                    }

                    if (!$o_definition->getRender()) {
                        // This element shouldn't be shown, continue
                        continue;
                    }

                    // Either this component or the entire form being readonly or disabled will make the component the same
                    $o_definition->setReadonly($o_definition->getReadonly() or $this->getReadonly());
                    $o_definition->setDisabled($o_definition->getDisabled() or $this->getDisabled());

                    if ($o_definition->getDisabled() or $o_definition->getReadonly()) {
                        // This is an immutable column. Do not add a column names as users  are not supposed to submit this.
                        $field_name = '';
                        $o_definition->setPrefix(null)->setColumn(null);
                    }

                    // Ensure security column values are never sent in the form
                    switch ($column) {
                        case 'password':
                            $source[$column] = '';
                    }

                    $execute = $o_definition->getExecute();

                    if (is_string($execute)) {
                        // Build the source execute array from the specified column
                        $items = explode(',', $execute);
                        $execute = [];

                        foreach ($items as $item) {
                            $execute[':' . $item] = array_get_safe($source, $item);
                        }
                    }

                    // Select default element
                    if (!$o_definition->getElement()) {
                        if ($o_definition->getSource()) {
                            // Default element for form items with a source is "select"
                            // TODO CHECK THIS! WHAT IF SOURCE IS A SINGLE STRING?
                            $o_definition->setElement(EnumElement::select);

                        } else {
                            // Default element for form items "text input"
                            $o_definition->setElement(EnumElement::input);
                        }
                    }

                    if ($o_definition->getDisplayCallback()) {
                        // Execute the specified callback on the data before displaying it
                        $source[$column] = $o_definition->getDisplayCallback()(array_get_safe($source, $column), $source);
                    }

                    // Set default value and override key entry values if value is null
                    if (array_get_safe($source, $column) === null) {
                        if ($o_definition->getNullElement()) {
                            $o_definition->setElement($o_definition->getNullElement());
                        }

                        if ($o_definition->getNullInputType()) {
                            $o_definition->setInputType($o_definition->getNullInputType());
                        }

                        if ($o_definition->getNullDisabled()) {
                            $o_definition->setDisabled($o_definition->getNullDisabled());
                        }

                        if ($o_definition->getNullReadonly()) {
                            $o_definition->setReadonly($o_definition->getNullReadonly());
                        }

                        $source[$column] = $o_definition->getNullDefault();
                    }

                    // Set value to value specified in $data
                    if ($o_definition->getValue()) {
                        $source[$column] = $o_definition->getValue();

                        // The specified value is an anonymous function, execute it to get the value out of it
                        if (is_callable($source[$column])) {
                            $source[$column] = $source[$column]();
                        }

                        // Apply variables
                        foreach ($source as $source_key => $source_value) {
                            if ($o_definitions->keyExists($source_key)) {
                                if (str_contains((string)$source[$column], ':' . $source_key)) {
                                    $source[$column] = str_replace(':' . $source_key, (string)$source_value, (string)$source[$column]);
                                }
                            }
                        }
                    }

                    // Build the form elements unless a component or content was specified manually
                    if (!$o_definition->getOutput()) {
                        switch ($o_definition->getElement()) {
                            case EnumElement::input:
                                if (!$o_definition->getInputType()) {
                                    throw new OutOfBoundsException(tr('No input type specified for column ":column / :field_name"', [
                                        ':field_name' => $field_name,
                                        ':column'     => $column,
                                    ]));
                                }

                                // If we have a source query specified, then get the actual value from the query
                                if ($o_definition->getSource()) {
                                    if (!is_array($o_definition->getSource())) {
                                        if (!is_string($o_definition->getSource())) {
                                            if ($o_definition->getSource() instanceof Stringable) {
                                                // This is a Stringable object
                                                $o_definition->setSource((string)$o_definition->getSource());

                                            } else {
                                                // The Only possibility left is instanceof PDOStatement
                                                $o_definition->setSource(sql()->getColumn($o_definition->getSource(), $execute));
                                            }
                                        }
                                    }
                                }

                                // Build the element class path and load the required class file
                                $type = match ($o_definition->getInputType()) {
                                    EnumInputType::datetime_local => 'DateTimeLocal',
                                    EnumInputType::auto_suggest   => 'AutoSuggest',
                                    default                       => str_replace(' ', '', Strings::camelCase(str_replace([' ', '-', '_',], ' ', $o_definition->getInputType()->value))),
                                };

                                // TODO Replace $field_name with the name and prefix from the Definition object
                                // Get the class for this element and ensure the library file is loaded
                                // Build the component, depending on the input type
                                $element_class = Library::includeClassFile('\\Phoundation\\Web\\Html\\Components\\Input\\Input' . $type);
                                $o_component   = match ($o_definition->getInputType()) {
                                    EnumInputType::number          => $element_class::new()
                                                                                    ->setDefinitionObject($o_definition)
                                                                                    ->setMin($o_definition->getMin())
                                                                                    ->setMax($o_definition->getMax())
                                                                                    ->setStep($o_definition->getStep())
                                                                                    ->setName($field_name)
                                                                                    ->setValue($source[$column]),

//                                    EnumInputType::datetime_local,
                                    EnumInputType::date            => $element_class::new()
                                                                                    ->setDefinitionObject($o_definition)
                                                                                    ->setMin($o_definition->getMin())
                                                                                    ->setMax($o_definition->getMax())
                                                                                    ->setName($field_name)
                                                                                    ->setValue($source[$column]),

                                    EnumInputType::auto_suggest    => $element_class::new()
                                                                                    ->setDefinitionObject($o_definition)
                                                                                    ->setAutoComplete(false)
                                                                                    ->setMinLength($o_definition->getMinLength())
                                                                                    ->setMaxLength($o_definition->getMaxLength())
                                                                                    ->setSourceUrl($o_definition->getSource())
                                                                                    ->setVariables($o_definition->getVariables())
                                                                                    ->setName($field_name)
                                                                                    ->setValue($source[$column]),

                                    EnumInputType::select          => $element_class::new()
                                                                                    ->setDefinitionObject($o_definition)
                                                                                    ->setName($field_name)
                                                                                    ->setValue($source[$column]),

                                    EnumInputType::checkbox        => $element_class::new()
                                                                                    ->setDefinitionObject($o_definition)
                                                                                    ->setName($field_name)
                                                                                    ->setValue('1')
                                                                                    ->setChecked((bool)$source[$column]),

                                    EnumInputType::delete_button   => DeleteButton::new()
                                                                                  ->setDefinitionObject($o_definition)
                                                                                  ->setHidden($o_definition->getHidden())
                                                                                  ->setValue($o_definition->getValue())
                                                                                  ->setContent($o_definition->getContent()),

                                    EnumInputType::save_button     => SaveButton::new()
                                                                                ->setDefinitionObject($o_definition)
                                                                                ->setHidden($o_definition->getHidden())
                                                                                ->setValue($o_definition->getValue())
                                                                                ->setContent($o_definition->getContent()),

                                    EnumInputType::back_button     => BackButton::new()
                                                                                ->setDefinitionObject($o_definition)
                                                                                ->setHidden($o_definition->getHidden())
                                                                                ->setValue($o_definition->getValue())
                                                                                ->setContent($o_definition->getContent()),

                                    EnumInputType::undelete_button => UndeleteButton::new()
                                                                                    ->setDefinitionObject($o_definition)
                                                                                    ->setHidden($o_definition->getHidden())
                                                                                    ->setValue($o_definition->getValue())
                                                                                    ->setContent($o_definition->getContent()),

                                    EnumInputType::lock_button     => LockButton::new()
                                                                                ->setDefinitionObject($o_definition)
                                                                                ->setHidden($o_definition->getHidden())
                                                                                ->setValue($o_definition->getValue())
                                                                                ->setContent($o_definition->getContent()),

                                    EnumInputType::unlock_button   => UnlockButton::new()
                                                                                ->setDefinitionObject($o_definition)
                                                                                ->setHidden($o_definition->getHidden())
                                                                                ->setValue($o_definition->getValue())
                                                                                ->setContent($o_definition->getContent()),

                                    EnumInputType::create_button   => CreateButton::new()
                                                                                  ->setDefinitionObject($o_definition)
                                                                                  ->setHidden($o_definition->getHidden())
                                                                                  ->setValue($o_definition->getValue())
                                                                                  ->setContent($o_definition->getContent()),

                                    EnumInputType::audit_button    => AuditButton::new()
                                                                                  ->setDefinitionObject($o_definition)
                                                                                  ->setHidden($o_definition->getHidden())
                                                                                  ->setValue($o_definition->getValue())
                                                                                  ->setContent($o_definition->getContent()),

                                    EnumInputType::reset,
                                    EnumInputType::button,
                                    EnumInputType::submit          => Button::new()
                                                                            ->setDefinitionObject($o_definition)
                                                                            ->setHidden($o_definition->getHidden())
                                                                            ->setValue($o_definition->getValue())
                                                                            ->setContent($o_definition->getContent()),

                                    // TODO This should be using ->setDefinitionObject($o_definition)!
                                    EnumInputType::hidden          => $element_class::new()
                                                                                    ->setRequired($o_definition->getRequired())
                                                                                    ->setName($field_name)
                                                                                    ->setValue($source[$column]),

                                    default                        => $element_class::new()
                                                                                    ->setDefinitionObject($o_definition)
                                                                                    ->setMinLength($o_definition->getMinLength())
                                                                                    ->setMaxLength($o_definition->getMaxLength())
                                                                                    ->setAutoComplete($o_definition->getAutoComplete())
                                                                                    ->setAutoSubmit($o_definition->getAutoSubmit())
                                                                                    ->setName($field_name)
                                                                                    ->setValue($source[$column])
                                };

                                $this->o_rows->add($o_definition, $o_component);
                                break;

                            case EnumElement::textarea:
                                // If we have a source query specified, then get the actual value from the query
                                if ($o_definition->getSource()) {
                                    $source[$column] = sql()->getColumn($o_definition->getSource(), $execute);
                                }

                                // Get the class for this element and ensure the library file is loaded
                                $element_class = Library::includeClassFile('\\Phoundation\\Web\\Html\\Components\\Input\\InputTextArea');
                                $o_component   = $element_class::new()
                                                               ->setDefinitionObject($o_definition)
                                                               ->setAutoComplete($o_definition->getAutoComplete())
                                                               ->setAutoSubmit($o_definition->getAutoSubmit())
                                                               ->setHidden($o_definition->getHidden())
                                                               ->setMaxLength($o_definition->getMaxLength())
                                                               ->setRows($o_definition->getRows())
                                                               ->setName($field_name)
                                                               ->setContent(array_get_safe($source, $column));

                                $this->o_rows->add($o_definition, $o_component);
                                break;

                            case EnumElement::div:
                                // no break;

                            case EnumElement::span:
                                // no break;

                            case EnumElement::label:
                                $element_class = Strings::capitalize($o_definition->getElement()->value);

                                // If we have a source query specified, then get the actual value from the query
                                if ($o_definition->getSource()) {
                                    $source[$column] = sql()->getColumn($o_definition->getSource(), $execute);
                                }

                                // Get the class for this element and ensure the library file is loaded
                                $element_class = Library::includeClassFile('\\Phoundation\\Web\\Html\\Components\\' . $element_class);
                                $o_component   = $element_class::new()
                                                               ->setDefinitionObject($o_definition)
                                                               ->setName($field_name)
                                                               ->setContent(array_get_safe($source, $column));

                                $this->o_rows->add($o_definition, $o_component);
                                break;

                            case EnumElement::button:
                                $element_class = Strings::capitalize($o_definition->getElement()->value);
                                $element_class = Library::includeClassFile('\\Phoundation\\Web\\Html\\Components\\Input\\Buttons\\' . $element_class);
                                $o_component   = $element_class::new()
                                                               ->setDefinitionObject($o_definition)
                                                               ->setName($field_name)
                                                               ->setContent($source[$column]);
                                $this->o_rows->add($o_definition, $o_component);
                                break;

                            case EnumElement::select:
                                // Get the class for this element and ensure the library file is loaded
                                $element_class = Library::includeClassFile('\\Phoundation\\Web\\Html\\Components\\Input\\InputSelect');
                                $o_component   = $element_class::new()
                                                               ->setDefinitionObject($o_definition)
                                                               ->setSource($o_definition->getSource(), $execute)
                                                               ->setDisabled($o_definition->getDisabled() or $o_definition->getReadonly())
                                                               ->setReadOnly((bool)$o_definition->getReadonly())
                                                               ->setHidden($o_definition->getHidden())
                                                               ->setName($field_name)
                                                               ->setAutoComplete($o_definition->getAutoComplete())
                                                               ->setAutoSubmit($o_definition->getAutoSubmit())
                                                               ->setSelected(array_get_safe($source, $column))
                                                               ->setAutoFocus($o_definition->getAutoFocus());

                                $this->o_rows->add($o_definition, $o_component);
                                break;

                            case EnumElement::inputmultibuttontext:
                                // Get the class for this element and ensure the library file is loaded
                                $element_class = Library::includeClassFile('\\Phoundation\\Web\\Html\\Components\\Input\\InputMultiButtonText');
                                $input = $element_class::new()
                                                       ->setSource($o_definition->getSource());

                                $input->getButton()
                                      ->setMode(EnumDisplayMode::from($o_definition->getMode()))
                                      ->setContent($o_definition->getLabel());

                                $o_component = $input->getInput()
                                                     ->setDefinitionObject($o_definition)
                                                     ->setHidden($o_definition->getHidden())
                                                     ->setName($field_name)
                                                     ->setValue($source[$column])
                                                     ->setContent(array_get_safe($source, $column))
                                                     ->setAutoFocus($o_definition->getAutoFocus());

                                $this->o_rows->add($o_definition, $o_component);
                                break;

                            case EnumElement::hr:
                                // Get the class for this element and ensure the library file is loaded
                                $element_class = Library::includeClassFile('\\Phoundation\\Web\\Html\\Components\\Hr');
                                $o_component   = $element_class::new();

                                $this->o_rows->add($o_definition, $o_component);
                                break;

                            case null:
                                throw new OutOfBoundsException(tr('No element specified for key ":key"', [
                                    ':key' => $column,
                                ]));

                            default:
                                if (!$o_definition->getElement()) {
                                    throw new OutOfBoundsException(tr('No element specified for key ":key"', [
                                        ':key' => $column,
                                    ]));
                                }

                                throw new OutOfBoundsException(tr('Unknown or unsupported element ":element" specified for key ":key"', [
                                    ':element' => $o_definition->getElement()->value,
                                    ':key'     => $column,
                                ]));
                        }

                    } elseif ($o_definition->getOutput()) {
                        if (is_callable($o_definition->getOutput())) {
                            // Component will be generated in a callback
                            if ($o_definition->getHidden()) {
                                $this->o_rows->add($o_definition, InputHidden::new()
                                                                             ->setName($field_name)
                                                                             ->setValue(Strings::force($source[$column], ' - ')));
                            } else {
                                $o_component = $o_definition->getOutput()($o_definition, $column, $field_name, $source);

                                if ($o_component) {
                                    if (!is_string($o_component)) {
                                        if (!$o_component instanceof RenderInterface) {
                                            // The content function did NOT return a render object
                                            throw new WebRenderException(tr('Failed to render DataEntryForm ":class", the column ":column" setContent method should return a RenderInterface object but returns a ":type" instead', [
                                                ':class'  => get_class($this->o_data_entry),
                                                ':column' => $column,
                                                ':type'   => get_class_or_datatype($o_component),
                                            ]));
                                        }

                                        $o_component->setDefinitionObject($o_definition);
                                    }

                                    $this->o_rows->add($o_definition, $o_component);
                                }
                            }

                        } else {
                            // Component has been defined directly
                            $this->o_rows->add($o_definition, $o_definition->getOutput());
                        }

//                    } elseif (is_callable($o_definition->getContent())) {
//                        // Content has been specified with a callback
//                        if ($o_definition->getHidden()) {
//                            $this->o_rows->add($o_definition, InputHidden::new()
//                                                                         ->setName($field_name)
//                                                                         ->setValue(Strings::force($source[$column], ' - ')));
//
//                        } else {
//                            $o_component = $o_definition->getContent()($o_definition, $column, $field_name, $source);
//
//                            if ($o_component) {
//                                if (!$o_component instanceof RenderInterface) {
//                                    // The content function did NOT return a render object
//                                    throw new WebRenderException(tr('Failed to render DataEntryForm ":class", the column ":column" setContent method should return a RenderInterface object but returns a ":type" instead', [
//                                        ':class'  => get_class($this->o_data_entry),
//                                        ':column' => $column,
//                                        ':type'   => get_class_or_datatype($o_component),
//                                    ]));
//                                }
//
//                                $this->o_rows->add($o_definition, $o_component);
//                            }
//                        }

                    } else {
                        // Content has already been rendered, display it
                        $this->o_rows->add($o_definition, $o_definition->getOutput());
                    }

                } catch (Throwable $e) {

                    if (empty($this->o_data_entry)) {
                        throw new FormsException(tr('Failed to render DataEntryForm column ":column"', [
                            ':column' => $column,
                        ]), $e);
                    }

                    throw new FormsException(tr('Failed to render DataEntryForm column ":column" for class ":class"', [
                        ':column' => $column,
                        ':class'  => get_class($this->o_data_entry),
                    ]), $e);
                }
            }

            // Add one empty element to (if required) close any rows
            static::$list_count++;

            // Add the data entry object name in the ID field
            // TODO Should we always do this?
            if (empty($this->o_data_entry)) {
                $return = '<div>' . $this->o_rows->render() . '</div>';

            } else {
                $return = '<div id="' . $this->o_data_entry->getObjectName() . ($this->o_data_entry->getId(false) ? '_' . $this->o_data_entry->getId(false) : null) . '" class="' . $this->o_data_entry->getObjectName() . '">
                              ' .$this->o_rows->render() . '
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
     * @param DefinitionInterface $o_definition
     * @param array               $source
     * @param mixed               $value
     *
     * @return mixed
     */
    protected function executePreRenderFunctions(DefinitionInterface $o_definition, array $source, mixed $value): mixed
    {
        // Execute all available pre-render functions
        foreach ($o_definition->getPreRenderFunctions() as $function) {
            $value = $function ($o_definition, $source, $value);
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
        return $this->o_definitions->getRenderMeta();
    }
}
