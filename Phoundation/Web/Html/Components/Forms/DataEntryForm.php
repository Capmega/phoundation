<?php
/**
 * Class DataEntryForm
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Forms;

use PDOStatement;
use Phoundation\Core\Libraries\Library;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Strings;
use Phoundation\Web\Exception\WebRenderException;
use Phoundation\Web\Html\Components\ElementsBlock;
use Phoundation\Web\Html\Components\Forms\Interfaces\DataEntryFormInterface;
use Phoundation\Web\Html\Components\Forms\Interfaces\DataEntryFormRowsInterface;
use Phoundation\Web\Html\Components\Input\InputHidden;
use Phoundation\Web\Html\Components\Input\Interfaces\RenderInterface;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumElement;
use Phoundation\Web\Html\Enums\EnumElementInputType;
use Stringable;

class DataEntryForm extends ElementsBlock implements DataEntryFormInterface
{
    /**
     * Counter for list forms
     *
     * @var int $list_count
     */
    protected static int $list_count = 0;

    /**
     * The key metadata for the specified data
     *
     * @var DefinitionsInterface|null $definitions
     */
    protected ?DefinitionsInterface $definitions = null;

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
     * @var DataEntryFormRowsInterface $rows
     */
    protected DataEntryFormRowsInterface $rows;

    /**
     * The data entry that generated this form
     *
     * @var DataEntryInterface $data_entry
     */
    protected DataEntryInterface $data_entry;


    /**
     * DataEntryForm class constructor
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     */
    public function __construct(IteratorInterface|PDOStatement|array|string|null $source = null)
    {
        parent::__construct($source);
        $this->rows = new DataEntryFormRows($this);
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
        $this->definitions->setMetaVisible($meta_visible);

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
     * Returns the data fields for this DataEntryForm
     *
     * @return DataEntryInterface|null
     */
    public function getDataEntry(): ?DataEntryInterface
    {
        return $this->data_entry;
    }


    /**
     * Set the data fields for this DataEntryForm
     *
     * @param DataEntryInterface $data_entry
     *
     * @return static
     */
    public function setDataEntry(DataEntryInterface $data_entry): static
    {
        $this->data_entry = $data_entry;

        return $this;
    }


    /**
     * Renders and returns the DataEntry as an HTML web form
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if (!$this->getDefinitionsObject()) {
            throw new OutOfBoundsException(tr('Cannot render DataEntryForm, no column definitions specified'));
        }
        $source        = $this->getSource();
        $definitions   = $this->getDefinitionsObject();
        $prefix        = $this->getDefinitionsObject()
                              ->getPrefix();
        $auto_focus_id = $this->getAutofocusId();
        if ($prefix) {
            if (str_ends_with((string) $prefix, '[]')) {
                // This is an array prefix with the closing tag attached, remove the closing tag
                $prefix = substr($prefix, 0, -1);
            }
            if (str_contains($prefix, '[]')) {
                // This prefix contains a [] to indicate a list item. Specify the correct ID's
                $prefix = str_replace('[]', '[' . static::$list_count . ']', $prefix);
            }
        }
        $is_array = str_ends_with((string) $prefix, '[');
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
         */ // If form key definitions are available, reorder the keys as in the form key definitions
        // Go over each key and add it to the form
        foreach ($definitions as $column => $definition) {
            // Add column name prefix
            $field_name = $prefix . $column;
            if ($field_name === $auto_focus_id) {
                // This column has autofocus
                $definition->setAutoFocus(true);
            }
            if ($is_array) {
                // The column name prefix is an HTML form array prefix, close that array
                $field_name .= ']';
            }
            if (!is_object($definition) or !($definition instanceof DefinitionInterface)) {
                throw new OutOfBoundsException(tr('Data key definition for column ":column / :field_name" is invalid. Iit should be an array or Definition type  but contains ":data"', [
                    ':column'     => $column,
                    ':field_name' => $field_name,
                    ':data'       => gettype($definition) . ': ' . $definition,
                ]));
            }
            if ($definition->isMeta()) {
                // This is an immutable meta column, virtual column, or readonly column.
                // In creation mode we're not even going to show this, in edit mode don't put a column name because
                // users aren't even supposed to be able to submit this
                if (empty($source['id'])) {
                    continue;
                }
                if (!$definitions->getMetaVisible()) {
                    continue;
                }
                $field_name = '';
            }
            if (!$definition->getRender()) {
                // This element shouldn't be shown, continue
                continue;
            }
            // Either the component or the entire form being readonly or disabled will make the component the same
            $definition->setReadonly($definition->getReadonly() or $this->getReadonly());
            $definition->setDisabled($definition->getDisabled() or $this->getDisabled());
            if ($definition->getDisabled() or $definition->getReadonly()) {
                // This is an immutable column. Don't add a column names as users aren't supposed to submit this.
                $field_name = '';
            }
            // Hidden objects have size 0
            if ($definition->getHidden()) {
                $definition->setSize(0);
            }
            // Ensure security column values are never sent in the form
            switch ($column) {
                case 'password':
                    $source[$column] = '';
            }
            $execute = $definition->getExecute();
            if (is_string($execute)) {
                // Build the source execute array from the specified column
                $items   = explode(',', $execute);
                $execute = [];
                foreach ($items as $item) {
                    $execute[':' . $item] = isset_get($source[$item]);
                }
            }
            // Select default element
            if (!$definition->getElement()) {
                if ($definition->getDataSource()) {
                    // Default element for form items with a source is "select"
                    // TODO CHECK THIS! WHAT IF SOURCE IS A SINGLE STRING?
                    $definition->setElement(EnumElement::select);
                } else {
                    // Default element for form items "text input"
                    $definition->setElement(EnumElement::input);
                }
            }
            if ($definition->getDisplayCallback()) {
                // Execute the specified callback on the data before displaying it
                $source[$column] = $definition->getDisplayCallback()(isset_get($source[$column]), $source);
            }
            // Set default value and override key entry values if value is null
            if (isset_get($source[$column]) === null) {
                if ($definition->getNullElement()) {
                    $definition->setElement($definition->getNullElement());
                }
                if ($definition->getNullInputType()) {
                    $definition->setInputType($definition->getNullInputType());
                }
                if ($definition->getNullDisabled()) {
                    $definition->setDisabled($definition->getNullDisabled());
                }
                if ($definition->getNullReadonly()) {
                    $definition->setReadonly($definition->getNullReadonly());
                }
                $source[$column] = $definition->getDefault();
            }
            // Set value to value specified in $data
            if ($definition->getValue()) {
                $source[$column] = $definition->getValue();
                // Apply variables
                foreach ($source as $source_key => $source_value) {
                    if ($definitions->keyExists($source_key)) {
                        $source[$column] = str_replace(':' . $source_key, (string) $source_value, $source[$column]);
                    }
                }
            }
            // Build the form elements
            if (!$definition->getContent()) {
                switch ($definition->getElement()) {
                    case 'input':
                        if (!$definition->getInputType()) {
                            throw new OutOfBoundsException(tr('No input type specified for column ":column / :field_name"', [
                                ':field_name' => $field_name,
                                ':column'     => $column,
                            ]));
                        }
                        // If we have a source query specified, then get the actual value from the query
                        if ($definition->getDataSource()) {
                            if (!is_array($definition->getDataSource())) {
                                if (!is_string($definition->getDataSource())) {
                                    if ($definition->getDataSource() instanceof Stringable) {
                                        // This is a Stringable object
                                        $definition->setDataSource((string) $definition->getDataSource());
                                    } else {
                                        // The Only possibility left is instanceof PDOStatement
                                        $definition->setDataSource(sql()->getColumn($definition->getDataSource(), $execute));
                                    }
                                }
                            }
                        }
                        // Build the element class path and load the required class file
                        $type = match ($definition->getInputType()) {
                            EnumElementInputType::datetime_local => 'DateTimeLocal',
                            EnumElementInputType::auto_suggest   => 'AutoSuggest',
                            default                              => str_replace(' ', '', Strings::camelCase(str_replace([
                                ' ',
                                '-',
                                '_',
                            ], ' ', $definition->getInputType()->value))),
                        };
                        // Get the class for this element and ensure the library file is loaded
                        // Build the component, depending on the input type
                        $element_class = Library::includeClassFile('\\Phoundation\\Web\\Html\\Components\\Input\\Input' . $type);
                        $component     = match ($definition->getInputType()) {
                            EnumElementInputType::number                               => $element_class::new()
                                                                                                        ->setDefinition($definition)
                                                                                                        ->setHidden($definition->getHidden())
                                                                                                        ->setRequired($definition->getRequired())
                                                                                                        ->setMin($definition->getMin())
                                                                                                        ->setMax($definition->getMax())
                                                                                                        ->setStep($definition->getStep())
                                                                                                        ->setValue($source[$column]),
                            EnumElementInputType::date                                 => $element_class::new()
                                                                                                        ->setDefinition($definition)
                                                                                                        ->setHidden($definition->getHidden())
                                                                                                        ->setRequired($definition->getRequired())
                                                                                                        ->setMin($definition->getMin())
                                                                                                        ->setMax($definition->getMax())
                                                                                                        ->setValue($source[$column]),
                            EnumElementInputType::auto_suggest                         => $element_class::new()
                                                                                                        ->setDefinition($definition)
                                                                                                        ->setHidden($definition->getHidden())
                                                                                                        ->setRequired($definition->getRequired())
                                                                                                        ->setAutoComplete(false)
                                                                                                        ->setMinLength($definition->getMinLength())
                                                                                                        ->setMaxLength($definition->getMaxLength())
                                                                                                        ->setSourceUrl($definition->getDataSource())
                                                                                                        ->setVariables($definition->getVariables())
                                                                                                        ->setValue($source[$column]),
                            EnumElementInputType::button, EnumElementInputType::submit => $element_class::new()
                                                                                                        ->setDefinition($definition)
                                                                                                        ->setHidden($definition->getHidden())
                                                                                                        ->setValue($source[$column]),
                            EnumElementInputType::select                               => $element_class::new()
                                                                                                        ->setDefinition($definition)
                                                                                                        ->setHidden($definition->getHidden())
                                                                                                        ->setRequired($definition->getRequired())
                                                                                                        ->setValue($source[$column]),
                            EnumElementInputType::checkbox                             => $element_class::new()
                                                                                                        ->setDefinition($definition)
                                                                                                        ->setHidden($definition->getHidden())
                                                                                                        ->setRequired($definition->getRequired())
                                                                                                        ->setValue('1')
                                                                                                        ->setChecked((bool) $source[$column]),
                            default                                                    => $element_class::new()
                                                                                                        ->setDefinition($definition)
                                                                                                        ->setHidden($definition->getHidden())
                                                                                                        ->setRequired($definition->getRequired())
                                                                                                        ->setMinLength($definition->getMinLength())
                                                                                                        ->setMaxLength($definition->getMaxLength())
                                                                                                        ->setAutoComplete($definition->getAutoComplete())
                                                                                                        ->setAutoSubmit($definition->getAutoSubmit())
                                                                                                        ->setValue($source[$column]),
                        };
                        $this->rows->add($definition, $component);
                        break;
                    case 'text':
                        // no-break
                    case 'textarea':
                        // If we have a source query specified, then get the actual value from the query
                        if ($definition->getDataSource()) {
                            $source[$column] = sql()->getColumn($definition->getDataSource(), $execute);
                        }
                        // Get the class for this element and ensure the library file is loaded
                        $element_class = Library::includeClassFile('\\Phoundation\\Web\\Html\\Components\\Input\\InputTextArea');
                        $component     = $element_class::new()
                                                       ->setDefinition($definition)
                                                       ->setAutoComplete($definition->getAutoComplete())
                                                       ->setAutoSubmit($definition->getAutoSubmit())
                                                       ->setHidden($definition->getHidden())
                                                       ->setMaxLength($definition->getMaxLength())
                                                       ->setRows($definition->getRows())
                                                       ->setContent(isset_get($source[$column]));
                        $this->rows->add($definition, $component);
                        break;
                    case 'div':
                        // no break;
                    case 'span':
                        $element_class = Strings::capitalize($definition->getElement());
                        // If we have a source query specified, then get the actual value from the query
                        if ($definition->getDataSource()) {
                            $source[$column] = sql()->getColumn($definition->getDataSource(), $execute);
                        }
                        // Get the class for this element and ensure the library file is loaded
                        $element_class = Library::includeClassFile('\\Phoundation\\Web\\Html\\Components\\' . $element_class);
                        $component     = $element_class::new()
                                                       ->setDefinition($definition)
                                                       ->setContent(isset_get($source[$column]));
                        $this->rows->add($definition, $component);
                        break;
                    case 'button':
                        $element_class = Strings::capitalize($definition->getElement());
                        $element_class = Library::includeClassFile('\\Phoundation\\Web\\Html\\Components\\' . $element_class);
                        $component     = $element_class::new()
                                                       ->setDefinition($definition)
                                                       ->setContent($source[$column]);
                        $this->rows->add($definition, $component);
                        break;
                    case 'select':
                        // Get the class for this element and ensure the library file is loaded
                        $element_class = Library::includeClassFile('\\Phoundation\\Web\\Html\\Components\\Input\\InputSelect');
                        $component     = $element_class::new()
                                                       ->setDefinition($definition)
                                                       ->setSource($definition->getDataSource(), $execute)
                                                       ->setDisabled($definition->getDisabled() or $definition->getReadonly())
                                                       ->setReadOnly((bool) $definition->getReadonly())
                                                       ->setHidden($definition->getHidden())
                                                       ->setName($field_name)
                                                       ->setAutoComplete($definition->getAutoComplete())
                                                       ->setAutoSubmit($definition->getAutoSubmit())
                                                       ->setSelected(isset_get($source[$column]))
                                                       ->setAutoFocus($definition->getAutoFocus());
                        $this->rows->add($definition, $component);
                        break;
                    case 'inputmultibuttontext':
                        // Get the class for this element and ensure the library file is loaded
                        $element_class = Library::includeClassFile('\\Phoundation\\Web\\Html\\Components\\Input\\InputMultiButtonText');
                        $input         = $element_class::new()
                                                       ->setSource($definition->getDataSource());
                        $input->getButton()
                              ->setMode(EnumDisplayMode::from($definition->getMode()))
                              ->setContent($definition->getLabel());
                        $component = $input->getInput()
                                           ->setDefinition($definition)
                                           ->setHidden($definition->getHidden())
                                           ->setName($field_name)
                                           ->setValue($source[$column])
                                           ->setContent(isset_get($source[$column]))
                                           ->setAutoFocus($definition->getAutoFocus());
                        $this->rows->add($definition, $component);
                        break;
                    case '':
                        throw new OutOfBoundsException(tr('No element specified for key ":key"', [
                            ':key' => $column,
                        ]));
                    default:
                        if (!is_callable($definition->getElement())) {
                            if (!$definition->getElement()) {
                                throw new OutOfBoundsException(tr('No element specified for key ":key"', [
                                    ':key' => $column,
                                ]));
                            }
                            throw new OutOfBoundsException(tr('Unknown element ":element" specified for key ":key"', [
                                ':element' => $definition->getElement(),
                                ':key'     => $column,
                            ]));
                        }
                        // Execute this to get the element
                        $this->rows->add($definition, $definition->getElement()($column, $definition, $source));
                }
            } elseif (is_callable($definition->getContent())) {
                if ($definition->getHidden()) {
                    $this->rows->add($definition, InputHidden::new()
                                                             ->setName($column)
                                                             ->setValue(Strings::force($source[$column], ' - ')));
                } else {
                    $component = $definition->getContent()($definition, $column, $field_name, $source);
                    if (!$component instanceof RenderInterface) {
                        // The content function did NOT return a render object
                        throw new WebRenderException(tr('Failed to render DataEntryForm ":class", the column ":column" setContent method should return a RenderInterface object but returns a ":type" instead', [
                            ':class'  => get_class($this->data_entry),
                            ':column' => $column,
                            ':type'   => get_object_class_or_data_type($component),
                        ]));
                    }
                    $this->rows->add($definition, $definition->getContent()($definition, $column, $field_name, $source));
                }
            } else {
                // Content has already been rendered, display it
                $this->rows->add($definition, $definition->getContent());
            }
        }
        // Add one empty element to (if required) close any rows
        static::$list_count++;

        if (empty($this->data_entry)) {
            return '<div>' . $this->rows->render() . '</div>';
        }

        return '<div id="' . $this->data_entry->getObjectName() . '">' . $this->rows->render() . '</div>';
    }


    /**
     * Returns the data fields for this DataEntryForm
     *
     * @return DefinitionsInterface|null
     */
    public function getDefinitionsObject(): ?DefinitionsInterface
    {
        return $this->definitions;
    }


    /**
     * Set the data source for this DataEntryForm
     *
     * @param DefinitionsInterface $definitions
     *
     * @return static
     */
    public function setDefinitionsObject(DefinitionsInterface $definitions): static
    {
        $this->definitions = $definitions;

        return $this;
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
     * @return $this
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
    public function getMetaVisible(): bool
    {
        return $this->definitions->getMetaVisible();
    }
}
