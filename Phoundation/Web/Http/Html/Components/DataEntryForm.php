<?php

namespace Phoundation\Web\Http\Html\Components;

use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Developer\Debug;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Url;


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
     * The data source for this form
     *
     * @var array $keys
     */
    protected array $keys;

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
     * Standard DataEntryForm object does not render any HTML, this requires a Template class
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if (!isset($this->source)) {
            throw new OutOfBoundsException(tr('Cannot render DataEntryForm, no data source specified'));
        }

        $html = '';

        // Possible $data contents:
        //
        // $data['display']  true   If false, this key will be completely ignored
        // $data['element']  input  Type of element, input, select, or text or callable function
        // $data['type']     text   Type of input element, if element is "input"
        // $data['readonly'] false  If true, will make the input element readonly
        // $data['label']    null
        // $data['source']   null   Query to get contents for select, or value from ID for readonly input element
        // $data['execute']  null   Array with bound execution variables for specified "source" query

        foreach ($this->keys as $key => $data) {
            if (!isset_get($data['display'], true)) {
                continue;
            }

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

            switch (isset_get($data['element'], 'input')) {
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

                    // Render the HTML for this element
                    $item = $element::new()
                        ->setDisabled((bool) isset_get($data['disabled'], false))
                        ->setReadOnly((bool) isset_get($data['readonly'], false))
                        ->setName($key)
                        ->setValue(isset_get($this->source[$key]))
                        ->render();
                    $html .= $this->renderItem($key, isset_get($data['label']), $item);

                    break;

                case 'text':
                    if (isset_get($data['source']) or $execute) {
                        throw new OutOfBoundsException(tr('Text element cannot have "source" or "execute" values for key ":key"', [
                            ':key' => $key
                        ]));
                    }

                    // Build the element class path and load the required class file
                    $element = '\Phoundation\Web\Http\Html\Components\Text';
                    $file    = Debug::getClassFile($element);
                    include_once($file);

                    $item = Text::new()
                        ->setDisabled((bool) isset_get($data['disabled'], false))
                        ->setReadOnly((bool) isset_get($data['readonly'], false))
                        ->setName($key)
                        ->setValue(isset_get($this->source[$key]))
                        ->render();
                    $html .= $this->renderItem($key, isset_get($data['label']), $item);
                    break;

                case 'select':
                    // Build the element class path and load the required class file
                    $element = '\Phoundation\Web\Http\Html\Components\Select';
                    $file    = Debug::getClassFile($element);
                    include_once($file);

                    $item = Select::new()
                        ->setSource(isset_get($data['source']), $execute)
                        ->setDisabled((bool) isset_get($data['disabled'], false))
                        ->setReadOnly((bool) isset_get($data['readonly'], false))
                        ->setName($key)
                        ->setValue(isset_get($this->source[$key]))
                        ->render();
                    $html .= $this->renderItem($key, isset_get($data['label']), $item);
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

        return $html;
    }



    /**
     * Renders and returns the HTML for this component
     *
     * @param string|int|null $id
     * @param string|null $label
     * @param string $html
     * @return string
     */
    protected function renderItem(string|int|null $id, ?string $label, string $html): string
    {
        $html = ' <div class="form-group">
                    <label for="' . $id . '">' . $label . '</label>
                    ' . $html . '
                  </div>';

        return $html;
    }
}