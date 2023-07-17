<?php

declare(strict_types=1);

namespace Templates\AdminLte\Html\Components;

use PDOStatement;
use Phoundation\Core\Arrays;
use Phoundation\Core\Libraries\Library;
use Phoundation\Core\Strings;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Components\Input\InputMultiButtonText;
use Phoundation\Web\Http\Html\Components\Input\InputSelect;
use Phoundation\Web\Http\Html\Components\Input\TextArea;
use Phoundation\Web\Http\Html\Components\Interfaces\ElementInterface;
use Phoundation\Web\Http\Html\Components\Interfaces\ElementsBlockInterface;
use Phoundation\Web\Http\Html\Enums\DisplayMode;
use Phoundation\Web\Http\Html\Html;
use Phoundation\Web\Http\Html\Renderer;
use Stringable;


/**
 * Class DataEntryForm
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class DataEntryForm extends Renderer
{
    /**
     * DataEntryForm class constructor
     *
     * @param ElementsBlockInterface|ElementInterface $element
     */
    public function __construct(ElementsBlockInterface|ElementInterface $element)
    {
        parent::__construct($element);
    }


    /**
     * Standard DataEntryForm object does not render any HTML, this requires a Template class
     *
     * @todo Refactor this method
     * @return string|null
     */
    public function render(): ?string
    {
        $render_object = $this->render_object;

        if (!$render_object->getDefinitions()) {
            throw new OutOfBoundsException(tr('Cannot render DataEntryForm, no fields specified'));
        }

        $source        = $render_object->getSource();
        $definitions   = $render_object->getDefinitions();
        $prefix        = $render_object->getDefinitions()->getPrefix();
        $auto_focus_id = $render_object->getAutofocusId();
        $array         = str_ends_with((string) $prefix, '[');

        /*
         * $data field keys: (Or just use Definitions class)
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
         */

        // If form key definitions are available, reorder the keys as in the form key definitions

        // Go over each key and add it to the form
        foreach ($definitions as $field => $definition) {
            // Add field name prefix
            $auto_focus = false;
            $field_name = $prefix . $field;

            if ($field_name === $auto_focus_id) {
                // This field has autofocus
                $auto_focus = true;
            }

            if ($array) {
                // The field name prefix is an HTML form array prefix, close that array
                $field_name .= ']';
            }

            if (is_array($definition)) {
                $definition_array = $definition;
            } else {
                if (!is_object($definition) or !($definition instanceof DefinitionInterface)) {
                    throw new OutOfBoundsException(tr('Data key definition for field ":field / :field_name" is invalid. Iit should be an array or Definition type  but contains ":data"', [
                        ':field'      => $field,
                        ':field_name' => $field_name,
                        ':data'       => gettype($definition) . ': ' . $definition
                    ]));
                }

                if ($definition->getMeta()) {
                    // This is an unmutable meta field, virtual field, or readonly field.
                    // In creation mode we're not even going to show this, in edit mode don't put a field name because
                    // users aren't even supposed to be able to submit this
                    if (empty($render_object->source['id'])) {
                        continue;
                    }

                    $field_name = '';
                }

                if (!$definition->getVisible()) {
                    // This element shouldn't be shown, continue
                    continue;
                }

                if ($definition->getDisabled() or $definition->getReadonly()) {
                    // This is an unmutable field. Don't add a field names as users aren't supposed to submit this.
                    $field_name = '';
                }

                // This is a new Definition object, get the definitions from there
                // TODO Use the Definition class all here,
                $definition_array = $definition->getRules();
            }

            // Set defaults
            Arrays::default($definition_array, 'disabled'    , false);
            Arrays::default($definition_array, 'label'       , null);
            Arrays::default($definition_array, 'max'         , null);
            Arrays::default($definition_array, 'maxlength'   , null);
            Arrays::default($definition_array, 'min'         , null);
            Arrays::default($definition_array, 'pattern'     , null);
            Arrays::default($definition_array, 'placeholder' , null);
            Arrays::default($definition_array, 'readonly'    , false);
            Arrays::default($definition_array, 'size'        , 12);
            Arrays::default($definition_array, 'source'      , null);
            Arrays::default($definition_array, 'step'        , null);
            Arrays::default($definition_array, 'title'       , null);
            Arrays::default($definition_array, 'type'        , 'text');
            Arrays::default($definition_array, 'virtual'     , false);
            Arrays::default($definition_array, 'visible'     , true);

            // Ensure password is never sent in the form
            switch ($field) {
                case 'password':
                    $source[$field] = '';
            }

            $execute = isset_get($definition_array['execute']);

            if (is_string($execute)) {
                // Build the source execute array from the specified column
                $items   = explode(',', $execute);
                $execute = [];

                foreach ($items as $item) {
                    $execute[':' . $item] = isset_get($source[$item]);
                }
            }

            // Select default element
            if (!isset_get($definition_array['element'])) {
                if (isset_get($definition_array['source'])) {
                    // Default element for form items with a source is "select"
                    $definition_array['element'] = 'select';
                } else {
                    // Default element for form items "text input"
                    $definition_array['element'] = 'input';
                }
            }

            // Set default value and override key entry values if value is null
            if (isset_get($source[$field]) === null) {
                if (isset_get($definition_array['null_element'])) {
                    $definition_array['element'] = $definition_array['null_element'];
                }

                if (isset_get($definition_array['null_type'])) {
                    $definition_array['type'] = $definition_array['null_type'];
                }

                if (isset_get($definition_array['null_disabled'])) {
                    $definition_array['disabled'] = $definition_array['null_disabled'];
                }

                if (isset_get($definition_array['null_readonly'])) {
                    $definition_array['readonly'] = $definition_array['null_readonly'];
                }

                $source[$field] = isset_get($definition_array['default']);
            }

            // Set value to value specified in $data
            if (isset($definition_array['value'])) {
                $source[$field] = $definition_array['value'];

                // Apply variables
                foreach ($source as $source_key => $source_value) {
                    $source[$field] = str_replace(':' . $source_key, (string) $source_value, $source[$field]);
                }
            }

            // Build the form elements
            if (empty($definition_array['content'])) {
                switch ($definition_array['element']) {
                    case 'input':
                        $definition_array['type'] = isset_get($definition_array['type'], 'text');

                        if (!$definition_array['type']) {
                            throw new OutOfBoundsException(tr('No input type specified for field ":field / field_name"', [
                                ':field_name' => $field_name,
                                ':field'      => $field
                            ]));
                        }

                        if (!$render_object->inputTypeSupported($definition_array['type'])) {
                            throw new OutOfBoundsException(tr('Unknown input type ":type" specified for field ":field / :field_name"', [
                                ':field_name' => $field_name,
                                ':field'      => $field,
                                ':type'       => $definition_array['type']
                            ]));
                        }

                        // If we have a source query specified, then get the actual value from the query
                        if (isset_get($definition_array['source'])) {
                            if (is_array($definition_array['source'])) {
                                $definition_array['source'] = $definition_array['source'];

                            } elseif (is_string($definition_array['source'])) {
                                $definition_array['source'] = $definition_array['source'];

                            } elseif ($definition_array['source'] instanceof Stringable) {
                                $definition_array['source'] = (string) $definition_array['source'];

                            } elseif ($definition_array['source'] instanceof PDOStatement) {
                                $definition_array['source'] = sql()->getColumn($definition_array['source'], $execute);
                            }
                        }

                        // Build the element class path and load the required class file
                        $type = match ($definition_array['type']) {
                            'datetime-local' => 'DateTimeLocal',
                            'auto-suggest'   => 'AutoSuggest',
                            default          => Strings::capitalize($definition_array['type']),
                        };

                        $element_class = '\\Phoundation\\Web\\Http\\Html\\Components\\Input\\Input' . $type;
                        $file          = Library::getClassFile($element_class);
                        include_once($file);

                        // Depending on input type we might need different code

                        switch ($definition_array['type']) {
                            case 'checkbox':
                                // Render the HTML for this element
                                $html = $element_class::new()
                                    ->setDisabled((bool) $definition_array['disabled'])
                                    ->setReadOnly((bool) $definition_array['readonly'])
                                    ->setClasses($definition->getClasses())
                                    ->setName($field_name)
                                    ->setValue('1')
                                    ->setChecked((bool) $source[$field])
                                    ->setAutoFocus($auto_focus)
                                    ->render();
                                break;

                            case 'number':
                                // Render the HTML for this element
                                $html = $element_class::new()
                                    ->setDisabled((bool) $definition_array['disabled'])
                                    ->setReadOnly((bool) $definition_array['readonly'])
                                    ->setMin(isset_get_typed('integer', $definition_array['min']))
                                    ->setMax(isset_get_typed('integer', $definition_array['max']))
                                    ->setStep(isset_get_typed('integer', $definition_array['step']))
                                    ->setClasses($definition->getClasses())
                                    ->setName($field_name)
                                    ->setValue($source[$field])
                                    ->setAutoFocus($auto_focus)
                                    ->render();
                                break;

                            case 'auto-suggest':
                                // Render the HTML for this element
                                $html = $element_class::new()
                                    ->setDisabled((bool) $definition_array['disabled'])
                                    ->setReadOnly((bool) $definition_array['readonly'])
                                    ->setAutoComplete(false)
                                    ->setMinLength(isset_get_typed('integer', $definition_array['minlength']))
                                    ->setMaxLength(isset_get_typed('integer', $definition_array['maxlength']))
                                    ->setSourceUrl(isset_get_typed('string', $definition_array['source']))
                                    ->setVariables($definition->getVariables())
                                    ->setClasses($definition->getClasses())
                                    ->setName($field_name)
                                    ->setValue($source[$field])
                                    ->setAutoFocus($auto_focus)
                                    ->render();
                                break;

                            case 'button':
                                // no break
                            case 'submit':
                                // Render the HTML for this element
                                $html = $element_class::new()
                                    ->setDisabled((bool) $definition_array['disabled'])
                                    ->setReadOnly((bool) $definition_array['readonly'])
                                    ->setName($field_name)
                                    ->setClasses($definition->getClasses())
                                    ->setValue($source[$field])
                                    ->setAutoFocus($auto_focus)
                                    ->render();
                                break;

                            default:
                                // Render the HTML for this element
                                $html = $element_class::new()
                                    ->setDisabled((bool) $definition_array['disabled'])
                                    ->setReadOnly((bool) $definition_array['readonly'])
                                    ->setMinLength(isset_get_typed('integer', $definition_array['minlength']))
                                    ->setMaxLength(isset_get_typed('integer', $definition_array['maxlength']))
                                    ->setAutoComplete($definition->getAutoComplete())
                                    ->setName($field_name)
                                    ->setClasses($definition->getClasses())
                                    ->setValue($source[$field])
                                    ->setAutoFocus($auto_focus)
                                    ->render();
                        }

                        $this->render .= $this->renderItem($field, $html, $definition_array);
                        break;

                    case 'text':
                        // no-break
                    case 'textarea':
                        // If we have a source query specified, then get the actual value from the query
                        if (isset_get($definition_array['source'])) {
                            $source[$field] = sql()->getColumn($definition_array['source'], $execute);
                        }

                        // Build the element class path and load the required class file
                        $element_class = '\\Phoundation\\Web\\Http\\Html\\Components\\Input\\TextArea';
                        $file          = Library::getClassFile($element_class);
                        include_once($file);

                        $html = TextArea::new()
                            ->setDisabled((bool) $definition_array['disabled'])
                            ->setReadOnly((bool) $definition_array['readonly'])
                            ->setMaxLength(isset_get_typed('integer', $definition_array['maxlength']))
                            ->setRows(isset_get_typed('integer', $definition_array['rows'], 5))
                            ->setAutoComplete($definition->getAutoComplete())
                            ->setClasses($definition->getClasses())
                            ->setName($field_name)
                            ->setContent(isset_get($source[$field]))
                            ->setAutoFocus($auto_focus)
                            ->render();

                        $this->render .= $this->renderItem($field, $html, $definition_array);
                        break;

                    case 'div':
                        // no break;
                    case 'span':
                        $element_class = Strings::capitalize($definition_array['element']);

                        // If we have a source query specified, then get the actual value from the query
                        if (isset_get($definition_array['source'])) {
                            $source[$field] = sql()->getColumn($definition_array['source'], $execute);
                        }

                        // Build the element class path and load the required class file
                        $element_class = '\\Phoundation\\Web\\Http\\Html\\Components\\' . $element_class;
                        $file          = Library::getClassFile($element_class);
                        include_once($file);

                        $html = $element_class::new()
                            ->setName($field_name)
                            ->setContent(isset_get($source[$field]))
                            ->setClasses($definition->getClasses())
                            ->setAutoFocus($auto_focus)
                            ->render();

                        $this->render .= $this->renderItem($field, $html, $definition_array);
                        break;

                    case 'select':
                        // Build the element class path and load the required class file
                        $element_class = '\\Phoundation\\Web\\Http\\Html\\Components\\Input\\InputSelect';
                        $file          = Library::getClassFile($element_class);
                        include_once($file);

                        $html = InputSelect::new()
                            ->setSource(isset_get($definition_array['source']), $execute)
                            ->setDisabled((bool) $definition_array['disabled'])
                            ->setReadOnly((bool) $definition_array['readonly'])
                            ->setClasses($definition->getClasses())
                            ->setName($field_name)
                            ->setAutoComplete($definition->getAutoComplete())
                            ->setSelected(isset_get($source[$field]))
                            ->setAutoFocus($auto_focus)
                            ->render();

                        $this->render .= $this->renderItem($field, $html, $definition_array);
                        break;

                    case 'inputmultibuttontext':
                        // Build the element class path and load the required class file
                        $element_class = '\\Phoundation\\Web\\Http\\Html\\Components\\Input\\InputMultiButtonText';
                        $file          = Library::getClassFile($element_class);
                        include_once($file);

                        $input = InputMultiButtonText::new()
                            ->setSource($definition_array['source']);

                        $input->getButton()
                            ->setMode(DisplayMode::from(isset_get($definition_array['mode'])))
                            ->setContent(isset_get($definition_array['label']));

                        $input->getInput()
                            ->setDisabled((bool) $definition_array['disabled'])
                            ->setReadOnly((bool) $definition_array['readonly'])
                            ->setName($field_name)
                            ->setClasses($definition->getClasses())
                            ->setValue($source[$field])
                            ->setContent(isset_get($source[$field]))
                            ->setAutoFocus($auto_focus);

                        $this->render .= $this->renderItem($field, $input->render(), $definition_array);
                        break;

                    case '':
                        throw new OutOfBoundsException(tr('No element specified for key ":key"', [
                            ':key' => $field
                        ]));

                    default:
                        if (!is_callable($definition_array['element'])) {
                            throw new OutOfBoundsException(tr('Unknown element ":element" specified for key ":key"', [
                                ':element' => isset_get($definition_array['element'], 'input'),
                                ':key'     => $field
                            ]));
                        }

                        // Execute this to get the element
                        $html = $definition_array['element']($field, $definition_array, $source);
                        $this->render .= $this->renderItem($field, $html, $definition_array);
                }

            } elseif(is_callable($definition_array['content'])) {
                $html          = $definition_array['content']($definition, $field, $field_name, $source);
                $this->render .= $this->renderItem($field, $html, $definition_array);

            } else {
                $this->render .= $this->renderItem($field, $definition_array['content'], $definition_array);
            }
        }

        // Add one empty element to (if required) close any rows
        $this->render .= $this->renderItem(null, null, null);
        return parent::render();
    }


    /**
     * Renders and returns the HTML for this component
     *
     * @param string|int|null $id
     * @param string|null $html
     * @param array|null $data
     * @return string|null
     */
    protected function renderItem(string|int|null $id, ?string $html, ?array $data): ?string
    {
        static $col_size = 12;
        static $cols     = [];

        $return = '';

        if ($data === null) {
            if ($col_size === 12) {
                // No row is open right now
                return '';
            }

            // Close the row
            $col_size = 0;

        } else {
            $cols[] = isset_get($data['label']) . '[' . $id . ']';

            // Keep track of column size, close each row when size 12 is reached
            if ($col_size === 12) {
                // Open a new row
                $return = '<div class="row">';
            }

            switch ($data['type']) {
                case 'checkbox':
                    $return .= '    <div class="col-sm-' . Html::safe($data['size']) . '">
                                        <div class="form-group">
                                            <label for="' . Html::safe($id) . '">' . Html::safe($data['label']) . '</label>
                                            <div class="form-check">
                                                ' . $html . '
                                                <label class="form-check-label" for="' . Html::safe($id) . '">' . Html::safe($data['label']) . '</label>
                                            </div>
                                        </div>
                                    </div>';
                    break;

                default:
                    $return .= '    <div class="col-sm-' . Html::safe($data['size']) . '">
                                        <div class="form-group">
                                            <label for="' . Html::safe($id) . '">' . Html::safe($data['label']) . '</label>
                                            ' . $html . '
                                        </div>
                                    </div>';
            }

            $col_size -= $data['size'];

            if ($col_size < 0) {
                throw OutOfBoundsException::new(tr('Cannot add column ":label" for ":class" form with size ":size", the row would surpass size 12 by ":count"', [
                    ':class' => $this->render_object->getDefinitions()->getTable(),
                    ':label' => $data['label'] . ' [' . $id . ']',
                    ':size'  => abs($data['size']),
                    ':count' => abs($col_size),
                ]))->setData(['Columns on this row' => $cols]);
            }
        }

        if ($col_size == 0) {
            // Close the row
            $col_size = 12;
            $cols = [];
            $return .= '</div>';
        }

        return $return;
    }
}