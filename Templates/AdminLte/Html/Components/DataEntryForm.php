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
        if (!$this->element->getFieldDefinitions()) {
            throw new OutOfBoundsException(tr('Cannot render DataEntryForm, no fields specified'));
        }

        $source = $this->element->getSource();
        $keys   = $this->element->getFieldDefinitions();


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
        foreach ($keys as $key => $data) {
            if (!is_array($data)) {
                if (!is_object($data) and !($data instanceof DefinitionInterface)) {
                    throw new OutOfBoundsException(tr('Data key definition for key ":key" is invalid. Iit should be an array or Definition type  but contains ":data"', [
                        ':key'  => $key,
                        ':data' => gettype($data) . ': ' . $data
                    ]));
                }

                // This is a new Definition object, get the definitions from there
                // TODO Use the Definition class all here,
                $data = $data->getDefinitions();
            }

            // Set defaults
            Arrays::default($data, 'size'        , 12);
            Arrays::default($data, 'type'        , 'text');
            Arrays::default($data, 'label'       , null);
            Arrays::default($data, 'disabled'    , false);
            Arrays::default($data, 'readonly'    , false);
            Arrays::default($data, 'visible'     , true);
            Arrays::default($data, 'readonly'    , false);
            Arrays::default($data, 'title'       , null);
            Arrays::default($data, 'placeholder' , null);
            Arrays::default($data, 'pattern'     , null);
            Arrays::default($data, 'maxlength'   , 0);
            Arrays::default($data, 'min'         , 0);
            Arrays::default($data, 'max'         , 0);
            Arrays::default($data, 'step'        , 0);

            if (!$data['visible']) {
                // This element shouldn't be shown, continue
                continue;
            }

            // Ensure password is never sent in the form
            switch ($key) {
                case 'password':
                    $source[$key] = '';
            }

            $execute = isset_get($data['execute']);

            if (is_string($execute)) {
                // Build the source execute array from the specified column
                $items   = explode(',', $execute);
                $execute = [];

                foreach ($items as $item) {
                    $execute[':' . $item] = isset_get($source[$item]);
                }
            }

            // Select default element
            if (!isset_get($data['element'])) {
                if (isset_get($data['source'])) {
                    // Default element for form items with a source is "select"
                    $data['element'] = 'select';
                } else {
                    // Default element for form items "text input"
                    $data['element'] = 'input';
                }
            }

            // Set default value and override key entry values if value is null
            if (isset_get($source[$key]) === null) {
                if (isset_get($data['null_element'])) {
                    $data['element'] = $data['null_element'];
                }

                if (isset_get($data['null_type'])) {
                    $data['type'] = $data['null_type'];
                }

                if (isset_get($data['null_disabled'])) {
                    $data['disabled'] = $data['null_disabled'];
                }

                if (isset_get($data['null_readonly'])) {
                    $data['readonly'] = $data['null_readonly'];
                }

                $source[$key] = isset_get($data['default']);
            }

            // Set value to value specified in $data
            if (isset($data['value'])) {
                $source[$key] = $data['value'];

                // Apply variables
                foreach ($source as $source_key => $source_value) {
                    $source[$key] = str_replace(':' . $source_key, (string) $source_value, $source[$key]);
                }
            }

            // Build the form elements
            switch ($data['element']) {
                case 'input':
                    $data['type'] = isset_get($data['type'], 'text');

                    if (!$data['type']) {
                        throw new OutOfBoundsException(tr('No input type specified for key ":key"', [
                            ':key' => $key
                        ]));
                    }

                    if (!$this->element->inputTypeSupported($data['type'])) {
                        throw new OutOfBoundsException(tr('Unknown input type ":type" specified for key ":key"', [
                            ':key'  => $key,
                            ':type' => $data['type']
                        ]));
                    }

                    // If we have a source query specified, then get the actual value from the query
                    if (isset_get($data['source'])) {
                        $source[$key] = sql()->getColumn($data['source'], $execute);
                    }

                    // Build the element class path and load the required class file
                    $type = match ($data['type']) {
                        'datetime-local' => 'DateTimeLocal',
                        default          => Strings::capitalize($data['type']),
                    };

                    $element = '\\Phoundation\\Web\\Http\\Html\\Components\\Input\\Input' . $type;
                    $file    = Library::getClassFile($element);
                    include_once($file);

                    // Depending on input type we might need different code
                    switch ($data['type']) {
                        case 'checkbox':
                            // Render the HTML for this element
                            $html = $element::new()
                                ->setDisabled((bool) $data['disabled'])
                                ->setReadOnly((bool) $data['readonly'])
                                ->setName($key)
                                ->setValue('1')
                                ->setChecked((bool) $source[$key])
                                ->render();
                            break;

                        case 'number':

                        case 'text':

                        default:
                            // Render the HTML for this element
                            $html = $element::new()
                                ->setDisabled((bool) $data['disabled'])
                                ->setReadOnly((bool) $data['readonly'])
                                ->setName($key)
                                ->setValue($source[$key])
                                ->render();
                    }

                    $this->render .= $this->renderItem($key, $html, $data);
                    break;

                case 'text':
                    // no-break
                case 'textarea':
                    // If we have a source query specified, then get the actual value from the query
                    if (isset_get($data['source'])) {
                        $source[$key] = sql()->getColumn($data['source'], $execute);
                    }

                    // Build the element class path and load the required class file
                    $element = '\\Phoundation\\Web\\Http\\Html\\Components\\Input\\TextArea';
                    $file    = Library::getClassFile($element);
                    include_once($file);

                    $html = TextArea::new()
                        ->setDisabled((bool) $data['disabled'])
                        ->setReadOnly((bool) $data['readonly'])
                        ->setRows((int) isset_get($data['rows'], 5))
                        ->setName($key)
                        ->setContent(isset_get($source[$key]))
                        ->render();

                    $this->render .= $this->renderItem($key, $html, $data);
                    break;

                case 'div':
                    // no break;
                case 'span':
                    $element = Strings::capitalize($data['element']);

                    // If we have a source query specified, then get the actual value from the query
                    if (isset_get($data['source'])) {
                        $source[$key] = sql()->getColumn($data['source'], $execute);
                    }

                    // Build the element class path and load the required class file
                    $element = '\\Phoundation\\Web\\Http\\Html\\Components\\' . $element;
                    $file    = Library::getClassFile($element);
                    include_once($file);

                    $html = $element::new()
                        ->setName($key)
                        ->setContent(isset_get($source[$key]))
                        ->render();

                    $this->render .= $this->renderItem($key, $html, $data);
                    break;

                case 'select':
                    // Build the element class path and load the required class file
                    $element = '\\Phoundation\\Web\\Http\\Html\\Components\\Input\\Select';
                    $file    = Library::getClassFile($element);
                    include_once($file);

                    $html = Select::new()
                        ->setSource(isset_get($data['source']), $execute)
                        ->setDisabled((bool) $data['disabled'])
                        ->setReadOnly((bool) $data['readonly'])
                        ->setName($key)
                        ->setSelected(isset_get($source[$key]))
                        ->render();

                    $this->render .= $this->renderItem($key, $html, $data);
                    break;

                case 'inputmultibuttontext':
                    // Build the element class path and load the required class file
                    $element = '\\Phoundation\\Web\\Http\\Html\\Components\\Input\\InputMultiButtonText';
                    $file    = Library::getClassFile($element);
                    include_once($file);

                    $input = InputMultiButtonText::new()
                        ->setSource($data['source']);

                    $input->getButton()
                        ->setMode(DisplayMode::from(isset_get($data['mode'])))
                        ->setContent(isset_get($data['label']));

                    $input->getInput()
                        ->setDisabled((bool) $data['disabled'])
                        ->setReadOnly((bool) $data['readonly'])
                        ->setName($key)
                        ->setValue($source[$key])
                        ->setContent(isset_get($source[$key]));

                    $this->render .= $this->renderItem($key, $input->render(), $data);
                    break;

                case '':
                    throw new OutOfBoundsException(tr('No element specified for key ":key"', [
                        ':key' => $key
                    ]));

                default:
                    if (!is_callable($data['element'])) {
                        throw new OutOfBoundsException(tr('Unknown element ":element" specified for key ":key"', [
                            ':element' => isset_get($data['element'], 'input'),
                            ':key'     => $key
                        ]));
                    }

                    // Execute this to get the element
                    $html = $data['element']($key, $data, $source);
                    $this->render .= $this->renderItem($key, $html, $data);
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
        $return = '';

        if ($data === null) {
            if ($col_size === 12) {
                // No row is open right now
                return '';
            }

            // Close the row
            $col_size = 0;

        } else {
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
                throw new OutOfBoundsException(tr('Cannot add column ":label" for ":class" form, the row would surpass size 12', [
                    ':class' => get_class($this->element),
                    ':label' => $data['label']
                ]));
            }
        }

        if ($col_size == 0) {
            // Close the row
            $col_size = 12;
            $return  .= '</div>';
        }

        return $return;
    }
}