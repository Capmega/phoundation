<?php

declare(strict_types=1);

namespace Templates\AdminLte\Html\Components;

use Phoundation\Core\Arrays;
use Phoundation\Core\Libraries\Library;
use Phoundation\Core\Strings;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Components\Input\InputMultiButtonText;
use Phoundation\Web\Http\Html\Components\Input\Select;
use Phoundation\Web\Http\Html\Components\Input\TextArea;
use Phoundation\Web\Http\Html\Components\Interfaces\ElementInterface;
use Phoundation\Web\Http\Html\Components\Interfaces\ElementsBlockInterface;
use Phoundation\Web\Http\Html\Enums\DisplayMode;
use Phoundation\Web\Http\Html\Html;
use Phoundation\Web\Http\Html\Renderer;
use Throwable;


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
        if (!$this->element->getDefinitions()) {
            throw new OutOfBoundsException(tr('Cannot render DataEntryForm, no fields specified'));
        }

        $source      = $this->element->getSource();
        $definitions = $this->element->getDefinitions();
        $prefix      = $this->element->getDefinitions()->getPrefix();
        $array       = str_ends_with((string) $prefix, '[');

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
            $field_name = $prefix . $field;

            if ($array) {
                // The field name prefix is an HTML form array prefix, close that array
                $field_name .= ']';
            }

            if (!is_array($definition)) {
                if (!is_object($definition) and !($definition instanceof DefinitionInterface)) {
                    throw new OutOfBoundsException(tr('Data key definition for field ":field / :field_name" is invalid. Iit should be an array or Definition type  but contains ":data"', [
                        ':field'      => $field,
                        ':field_name' => $field_name,
                        ':data'       => gettype($definition) . ': ' . $definition
                    ]));
                }

                // This is a new Definition object, get the definitions from there
                // TODO Use the Definition class all here,
                $definition = $definition->getDefinitions();
            }

            // Set defaults
            Arrays::default($definition, 'size'        , 12);
            Arrays::default($definition, 'type'        , 'text');
            Arrays::default($definition, 'label'       , null);
            Arrays::default($definition, 'disabled'    , false);
            Arrays::default($definition, 'readonly'    , false);
            Arrays::default($definition, 'visible'     , true);
            Arrays::default($definition, 'virtual'     , false);
            Arrays::default($definition, 'readonly'    , false);
            Arrays::default($definition, 'title'       , null);
            Arrays::default($definition, 'placeholder' , null);
            Arrays::default($definition, 'pattern'     , null);
            Arrays::default($definition, 'maxlength'   , 0);
            Arrays::default($definition, 'min'         , 0);
            Arrays::default($definition, 'max'         , 0);
            Arrays::default($definition, 'step'        , 0);

            if (!$definition['visible'] or $definition['virtual']) {
                // This element shouldn't be shown, continue
                continue;
            }

            // Ensure password is never sent in the form
            switch ($field) {
                case 'password':
                    $source[$field] = '';
            }

            $execute = isset_get($definition['execute']);

            if (is_string($execute)) {
                // Build the source execute array from the specified column
                $items   = explode(',', $execute);
                $execute = [];

                foreach ($items as $item) {
                    $execute[':' . $item] = isset_get($source[$item]);
                }
            }

            // Select default element
            if (!isset_get($definition['element'])) {
                if (isset_get($definition['source'])) {
                    // Default element for form items with a source is "select"
                    $definition['element'] = 'select';
                } else {
                    // Default element for form items "text input"
                    $definition['element'] = 'input';
                }
            }

            // Set default value and override key entry values if value is null
            if (isset_get($source[$field]) === null) {
                if (isset_get($definition['null_element'])) {
                    $definition['element'] = $definition['null_element'];
                }

                if (isset_get($definition['null_type'])) {
                    $definition['type'] = $definition['null_type'];
                }

                if (isset_get($definition['null_disabled'])) {
                    $definition['disabled'] = $definition['null_disabled'];
                }

                if (isset_get($definition['null_readonly'])) {
                    $definition['readonly'] = $definition['null_readonly'];
                }

                $source[$field] = isset_get($definition['default']);
            }

            // Set value to value specified in $data
            if (isset($definition['value'])) {
                $source[$field] = $definition['value'];

                // Apply variables
                foreach ($source as $source_key => $source_value) {
                    $source[$field] = str_replace(':' . $source_key, (string) $source_value, $source[$field]);
                }
            }

            // Build the form elements
            switch ($definition['element']) {
                case 'input':
                    $definition['type'] = isset_get($definition['type'], 'text');

                    if (!$definition['type']) {
                        throw new OutOfBoundsException(tr('No input type specified for field ":field / field_name"', [
                            ':field_name' => $field_name,
                            ':field'      => $field
                        ]));
                    }

                    if (!$this->element->inputTypeSupported($definition['type'])) {
                        throw new OutOfBoundsException(tr('Unknown input type ":type" specified for field ":field / :field_name"', [
                            ':field_name' => $field_name,
                            ':field'      => $field,
                            ':type'       => $definition['type']
                        ]));
                    }

                    // If we have a source query specified, then get the actual value from the query
                    if (isset_get($definition['source'])) {
                        $source[$field] = sql()->getColumn($definition['source'], $execute);
                    }

                    // Build the element class path and load the required class file
                    $type = match ($definition['type']) {
                        'datetime-local' => 'DateTimeLocal',
                        default          => Strings::capitalize($definition['type']),
                    };

                    $element = '\\Phoundation\\Web\\Http\\Html\\Components\\Input\\Input' . $type;
                    $file    = Library::getClassFile($element);
                    include_once($file);

                    // Depending on input type we might need different code
                    switch ($definition['type']) {
                        case 'checkbox':
                            // Render the HTML for this element
                            $html = $element::new()
                                ->setDisabled((bool) $definition['disabled'])
                                ->setReadOnly((bool) $definition['readonly'])
                                ->setName($field_name)
                                ->setValue('1')
                                ->setChecked((bool) $source[$field])
                                ->render();
                            break;

                        case 'number':
                            // Render the HTML for this element
                            $html = $element::new()
                                ->setDisabled((bool) $definition['disabled'])
                                ->setReadOnly((bool) $definition['readonly'])
                                ->setMin(isset_get($definition['min']))
                                ->setMax(isset_get($definition['max']))
                                ->setStep(isset_get($definition['step']))
                                ->setName($field_name)
                                ->setValue($source[$field])
                                ->render();

                        case 'text':
                            // no break
                        default:
                            // Render the HTML for this element
                            $html = $element::new()
                                ->setDisabled((bool) $definition['disabled'])
                                ->setReadOnly((bool) $definition['readonly'])
                                ->setName($field_name)
                                ->setValue($source[$field])
                                ->render();
                    }

                    $this->render .= $this->renderItem($field, $html, $definition);
                    break;

                case 'text':
                    // no-break
                case 'textarea':
                    // If we have a source query specified, then get the actual value from the query
                    if (isset_get($definition['source'])) {
                        $source[$field] = sql()->getColumn($definition['source'], $execute);
                    }

                    // Build the element class path and load the required class file
                    $element = '\\Phoundation\\Web\\Http\\Html\\Components\\Input\\TextArea';
                    $file    = Library::getClassFile($element);
                    include_once($file);

                    $html = TextArea::new()
                        ->setDisabled((bool) $definition['disabled'])
                        ->setReadOnly((bool) $definition['readonly'])
                        ->setRows((int) isset_get($definition['rows'], 5))
                        ->setName($field_name)
                        ->setContent(isset_get($source[$field]))
                        ->render();

                    $this->render .= $this->renderItem($field, $html, $definition);
                    break;

                case 'div':
                    // no break;
                case 'span':
                    $element = Strings::capitalize($definition['element']);

                    // If we have a source query specified, then get the actual value from the query
                    if (isset_get($definition['source'])) {
                        $source[$field] = sql()->getColumn($definition['source'], $execute);
                    }

                    // Build the element class path and load the required class file
                    $element = '\\Phoundation\\Web\\Http\\Html\\Components\\' . $element;
                    $file    = Library::getClassFile($element);
                    include_once($file);

                    $html = $element::new()
                        ->setName($field_name)
                        ->setContent(isset_get($source[$field]))
                        ->render();

                    $this->render .= $this->renderItem($field, $html, $definition);
                    break;

                case 'select':
                    // Build the element class path and load the required class file
                    $element = '\\Phoundation\\Web\\Http\\Html\\Components\\Input\\Select';
                    $file    = Library::getClassFile($element);
                    include_once($file);

                    $html = Select::new()
                        ->setSource(isset_get($definition['source']), $execute)
                        ->setDisabled((bool) $definition['disabled'])
                        ->setReadOnly((bool) $definition['readonly'])
                        ->setName($field_name)
                        ->setSelected(isset_get($source[$field]))
                        ->render();

                    $this->render .= $this->renderItem($field, $html, $definition);
                    break;

                case 'inputmultibuttontext':
                    // Build the element class path and load the required class file
                    $element = '\\Phoundation\\Web\\Http\\Html\\Components\\Input\\InputMultiButtonText';
                    $file    = Library::getClassFile($element);
                    include_once($file);

                    $input = InputMultiButtonText::new()
                        ->setSource($definition['source']);

                    $input->getButton()
                        ->setMode(DisplayMode::from(isset_get($definition['mode'])))
                        ->setContent(isset_get($definition['label']));

                    $input->getInput()
                        ->setDisabled((bool) $definition['disabled'])
                        ->setReadOnly((bool) $definition['readonly'])
                        ->setName($field_name)
                        ->setValue($source[$field])
                        ->setContent(isset_get($source[$field]));

                    $this->render .= $this->renderItem($field, $input->render(), $definition);
                    break;

                case '':
                    throw new OutOfBoundsException(tr('No element specified for key ":key"', [
                        ':key' => $field
                    ]));

                default:
                    if (!is_callable($definition['element'])) {
                        throw new OutOfBoundsException(tr('Unknown element ":element" specified for key ":key"', [
                            ':element' => isset_get($definition['element'], 'input'),
                            ':key'     => $field
                        ]));
                    }

                    // Execute this to get the element
                    $html = $definition['element']($field, $definition, $source);
                    $this->render .= $this->renderItem($field, $html, $definition);
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
                throw OutOfBoundsException::new(tr('Cannot add column ":label" for ":class" form, the row would surpass size 12 by ":count"', [
                    ':class' => get_class($this->element),
                    ':label' => $data['label'] . ' [' . $id . ']',
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