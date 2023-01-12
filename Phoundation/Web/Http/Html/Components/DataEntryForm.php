<?php

namespace Phoundation\Web\Http\Html\Components;

use Composer\XdebugHandler\Process;
use Phoundation\Core\Arrays;
use Phoundation\Core\Strings;
use Phoundation\Developer\Debug;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Json;
use Phoundation\Web\Http\Html\Components\Input\Select;
use Phoundation\Web\Http\Html\Components\Input\TextArea;



/**
 * Class DataEntryForm
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Web
 */
class DataEntryForm extends ElementsBlock
{
    /**
     * The key metadata for the specified data
     *
     * @var array $keys
     */
    protected array $keys;

    /**
     * The form specific metadata for the keys for the specified data
     *
     * @var array $form_keys
     */
    protected array $form_keys;

    /**
     * Optional class for input elements
     *
     * @var string $input_class
     */
    protected string $input_class;

    /**
     * Supported input element types
     *
     * @var array[] $supported_input
     */
    protected array $supported_input = [
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
     * @return static
     */
    public function setInputClass(string $input_class): static
    {
        $this->input_class = $input_class;
        return $this;
    }



    /**
     * Returns the data source for this DataEntryForm
     *
     * @return array
     */
    public function getKeys(): array
    {
        return $this->keys;
    }



    /**
     * Set the data source for this DataEntryForm
     *
     * @param array $keys
     * @return static
     */
    public function setKeys(array $keys): static
    {
        $this->keys = $keys;
        return $this;
    }



    /**
     * Returns the data source for this DataEntryForm
     *
     * @return array
     */
    public function getFormKeys(): array
    {
        return $this->form_keys;
    }



    /**
     * Set the data source for this DataEntryForm
     *
     * @param array $form_keys
     * @return static
     */
    public function setFormKeys(array $form_keys): static
    {
        $this->form_keys = $form_keys;
        return $this;
    }



    /**
     * Standard DataEntryForm object does not render any HTML, this requires a Template class
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if (!isset($this->source)) {
            throw new OutOfBoundsException(tr('Cannot render DataEntryForm, no data source specified'));
        }

        // Possible $data contents:
        //
        // $data['display']  true   If false, this key will be completely ignored
        // $data['element']  input  Type of element, input, select, or text or callable function
        // $data['type']     text   Type of input element, if element is "input"
        // $data['readonly'] false  If true, will make the input element readonly
        // $data['label']    null
        // $data['source']   null   Query to get contents for select, or value from ID for readonly input element
        // $data['execute']  null   Array with bound execution variables for specified "source" query

        // If form key definitions are available, reorder the keys as in the form key definitions
        if ($this->form_keys) {
            $this->reorderKeys();
        }

        // Go over each key and add it to the form
        foreach ($this->keys as $key => $data) {
            if (!is_array($data)) {
                throw new OutOfBoundsException(tr('Data key definition for key ":key" is invalid. Iit should be an array but contains ":data"', [
                    ':key'  => $key,
                    ':data' => gettype($data) . ': ' . $data
                ]));
            }

            if (!isset_get($data['display'], true)) {
                continue;
            }

            // Set defaults
            Arrays::default($data, 'size'    , 12);
            Arrays::default($data, 'type'    , 'text');
            Arrays::default($data, 'label'   , null);
            Arrays::default($data, 'disabled', false);
            Arrays::default($data, 'readonly', false);

            // Ensure password is never sent in the form
            switch ($key) {
                case 'password':
                    $this->source[$key] = '';
            }

            $execute = isset_get($data['execute']);

            if (is_string($execute)) {
                // Build the source execute array from the specified column
                $items   = explode(',', $execute);
                $execute = [];

                foreach ($items as $item) {
                    $execute[':' . $item] = isset_get($this->source[$item]);
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
            if (isset_get($this->source[$key]) === null) {
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

                $this->source[$key] = isset_get($data['default']);
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

                    if (!in_array($data['type'], $this->supported_input)) {
                        throw new OutOfBoundsException(tr('Unknown input type ":type" specified for key ":key"', [
                            ':key'  => $key,
                            ':type' => $data['type']
                        ]));
                    }

                    // If we have a source query specified, then get the actual value from the query
                    if (isset_get($data['source'])) {
                        $this->source[$key] = sql()->getColumn($data['source'], $execute);
                    }

                    // Build the element class path and load the required class file
                    $element = '\Phoundation\Web\Http\Html\Components\Input\Input' . Strings::capitalize($data['type']);
                    $file    = Debug::getClassFile($element);
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
                                ->setChecked((bool) $this->source[$key])
                                ->render();
                            break;

                        default:
                            // Render the HTML for this element
                            $html = $element::new()
                                ->setDisabled((bool) $data['disabled'])
                                ->setReadOnly((bool) $data['readonly'])
                                ->setName($key)
                                ->setValue($this->source[$key])
                                ->render();
                    }

                    $this->render .= $this->renderItem($key, $html, $data);
                    break;

                case 'text':
                    // no-break
                case 'textarea':
                // If we have a source query specified, then get the actual value from the query
                if (isset_get($data['source'])) {
                    $this->source[$key] = sql()->getColumn($data['source'], $execute);
                }

                // Build the element class path and load the required class file
                    $element = '\Phoundation\Web\Http\Html\Components\Input\TextArea';
                    $file    = Debug::getClassFile($element);
                    include_once($file);

                    $html = TextArea::new()
                        ->setDisabled((bool) $data['disabled'])
                        ->setReadOnly((bool) $data['readonly'])
                        ->setRows((int) isset_get($data['rows'], 5))
                        ->setName($key)
                        ->setValue(isset_get($this->source[$key]))
                        ->render();

                    $this->render .= $this->renderItem($key, $html, $data);
                    break;

                case 'select':
                    // Build the element class path and load the required class file
                    $element = '\Phoundation\Web\Http\Html\Components\Input\Select';
                    $file    = Debug::getClassFile($element);
                    include_once($file);

                    $html = Select::new()
                        ->setSource(isset_get($data['source']), $execute)
                        ->setDisabled((bool) $data['disabled'])
                        ->setReadOnly((bool) $data['readonly'])
                        ->setName($key)
                        ->setSelected(isset_get($this->source[$key]))
                        ->render();

                    $this->render .= $this->renderItem($key, $html, $data);
                    break;

                case '':
                    throw new OutOfBoundsException(tr('No element specified for key ":key"', [
                        ':key' => $key
                    ]));

                default:
                    throw new OutOfBoundsException(tr('Unknown element ":element" specified for key ":key"', [
                        ':element' => isset_get($data['element'], 'input'),
                        ':key'     => $key
                    ]));
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
        if ($data === null) {
            // Return empty, this is just one extra call to this method in case any open rows need closing.
            // This implementation of this method does not use rows, so just return empty.
            return '';
        }

        return '<label for="' . $id . '">' . $data['label'] . '</label>' . $html;
    }



    /**
     * Reorder the keys in the order of the specified keys and add the size information
     *
     * @return void
     */
    protected function reorderKeys(): void
    {
        $keys = [];

        foreach ($this->form_keys as $key => $size) {
            if (!array_key_exists($key, $this->keys)) {
                throw new OutOfBoundsException(tr('Specified form key ":key" does not exist as DataEntry key', [
                    ':key'
                ]));
            }

            $keys[$key]         = $this->keys[$key];
            $keys[$key]['size'] = $size;
        }

        $this->keys = $keys;
    }
}