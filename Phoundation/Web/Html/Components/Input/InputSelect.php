<?php
/**
 * class InputSelect
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Data\Iterator;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Input\Interfaces\InputInterface;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;
use Phoundation\Web\Html\Components\Interfaces\ElementInterface;
use Phoundation\Web\Html\Components\ResourceElement;
use Phoundation\Web\Html\Traits\TraitBeforeAfterButtons;
use Stringable;
use Throwable;

class InputSelect extends ResourceElement implements InputSelectInterface, InputInterface
{
    use TraitBeforeAfterButtons;


    /**
     * The class for the <option> elements within the <select> element
     *
     * @var array $option_classes
     */
    protected array $option_classes = [];

    /**
     * The HTML class element attribute cache for the <option> element
     *
     * @var string|null
     */
    protected ?string $option_class = null;

    /**
     * The list of item(s) that are selected in this select element
     *
     * @var array $selected
     */
    protected array $selected = [];

    /**
     * If set, the element will attempt to automatically select an item
     *
     * @var bool
     */
    protected bool $auto_select = false;

    /**
     * The data column that will contain the keys. If not specified, the first column will be assumed
     *
     * @var string|null
     */
    protected ?string $key_column = null;

    /**
     * The data column that will contain the values. If not specified, the last column will be assumed
     *
     * @var string|null
     */
    protected ?string $value_column = null;


    /**
     * Select constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        parent::__construct($content);
        parent::setElement('select');
    }


    /**
     * Returns the data column that will contain the keys. If not specified, the first column will be assumed
     *
     * @return string|null
     */
    public function getKeyColumn(): ?string
    {
        return $this->key_column;
    }


    /**
     * Sets the data column that will contain the keys. If not specified, the first column will be assumed
     *
     * @param string|null $key_column
     *
     * @return static
     */
    public function setKeyColumn(?string $key_column): static
    {
        $this->key_column = $key_column;

        return $this;
    }


    /**
     * Returns the data column that will contain the values. If not specified, the first column will be assumed
     *
     * @return string|null
     */
    public function getValueColumn(): ?string
    {
        return $this->value_column;
    }


    /**
     * Sets the data column that will contain the values. If not specified, the first column will be assumed
     *
     * @param string|null $value_column
     *
     * @return static
     */
    public function setValueColumn(?string $value_column): static
    {
        $this->value_column = $value_column;

        return $this;
    }


    /**
     * Returns the HTML readonly element attribute
     *
     * Enabling readonly on a select element will also enable disabled!
     *
     * @return bool
     */
    public function getReadonly(): bool
    {
        return $this->readonly;
    }


    /**
     * Set the HTML readonly element attribute
     *
     * Enabling readonly on a select element will also enable disabled!
     *
     * @param bool $readonly
     *
     * @return static
     */
    public function setReadonly(bool $readonly): static
    {
        if ($readonly) {
            if (!$this->readonly) {
                $this->addClasses('readonly');
            }
            if (!$this->disabled) {
                $this->addClasses('disabled');
            }
            $this->readonly = true;
            $this->disabled = true;

        } else {
            $this->removeClasses('readonly');
            $this->readonly = false;
        }

        return $this;
    }


    /**
     * Returns  the HTML disabled element attribute
     *
     * Enabling readonly on a select element will also enable disabled!
     *
     * @return bool
     */
    public function getDisabled(): bool
    {
        return $this->disabled;
    }


    /**
     * Set the HTML disabled element attribute
     *
     * @param bool $disabled
     *
     * @return static
     */
    public function setDisabled(bool $disabled): static
    {
        if ($disabled) {
            $this->addClasses('disabled');
            $this->disabled = true;

        } else {
            if (!$this->readonly) {
                $this->removeClasses('disabled');
                $this->disabled = false;
            }
        }

        return $this;
    }


    /**
     * Sets if the select element allows multiple options to be selected
     *
     * @param bool $multiple
     *
     * @return static
     */
    public function setMultiple(bool $multiple): static
    {
        return $this->setAttribute($multiple, 'multiple');
    }


    /**
     * Returns if the select element has a search
     *
     * @return bool
     */
    public function getSearch(): bool
    {
        return (bool) $this->attributes->get('search', false);
    }


    /**
     * Sets if the select element has a search
     *
     * @param bool $search
     *
     * @return static
     */
    public function setSearch(bool $search): static
    {
        return $this->setAttribute($search, 'search');
    }


    /**
     * Returns if the select element has a clear_button
     *
     * @return bool
     */
    public function getClearButton(): bool
    {
        return (bool) $this->attributes->get('clear_button', false);
    }


    /**
     * Sets if the select element has a clear_button
     *
     * @param bool $clear_button
     *
     * @return static
     */
    public function setClearButton(bool $clear_button): static
    {
        return $this->setAttribute($clear_button, 'clear_button');
    }


    /**
     * Returns if the select element has custom_content
     *
     * @return string|null
     */
    public function getCustomContent(): ?string
    {
        return $this->attributes->get('custom_content', false);
    }


    /**
     * Sets if the select element has custom_content
     *
     * @param string|null $custom_content
     *
     * @return static
     */
    public function setCustomContent(?string $custom_content): static
    {
        return $this->setAttribute($custom_content, 'custom_content');
    }


    /**
     * Returns the auto complete setting
     *
     * @return bool
     */
    public function getAutoComplete(): bool
    {
        return Strings::toBoolean($this->attributes->get('autocomplete', false));
    }


    /**
     * Sets the auto complete setting
     *
     * @param bool $auto_complete
     *
     * @return $this
     */
    public function setAutoComplete(bool $auto_complete): static
    {
        return $this->setAttribute($auto_complete ? 'on' : 'off', 'autocomplete');
    }


    /**
     * Returns if there is only one option, it should automatically be selected
     *
     * @return bool
     */
    public function getAutoSelect(): bool
    {
        return $this->auto_select;
    }


    /**
     * Sets if there is only one option, it should automatically be selected
     *
     * @param bool $auto_select
     *
     * @return static
     */
    public function setAutoSelect(bool $auto_select): static
    {
        $this->auto_select = $auto_select;

        return $this;
    }


    /**
     * Enables auto select
     *
     * @return static
     * @see \Templates\AdminLte\Html\Components\Input\TemplateInputSelect::setAutoSelect()
     */
    public function enableAutoSelect(): static
    {
        $this->auto_select = true;

        return $this;
    }


    /**
     * Disables auto select
     *
     * @return static
     * @see \Templates\AdminLte\Html\Components\Input\TemplateInputSelect::setAutoSelect()
     */
    public function disableAutoSelect(): static
    {
        $this->auto_select = false;

        return $this;
    }


    /**
     * Clear multiple selected options
     *
     * @return static
     */
    public function clearSelected(): static
    {
        $this->selected = [];

        return $this;
    }


    /**
     * Returns the selected option(s)
     *
     * @return array|string|int|null
     */
    public function getSelected(): array|string|int|null
    {
        return $this->selected;
    }


    /**
     * Sets multiple selected options
     *
     * @param array|string|int|null $selected
     * @param bool                  $value
     *
     * @return static
     */
    public function setSelected(array|string|int|null $selected = null, bool $value = false): static
    {
        $this->selected = [];

        return $this->addSelected($selected, $value);
    }


    /**
     * Adds a single or multiple selected options
     *
     * @param array|string|int|null $selected
     * @param bool                  $value
     *
     * @return static
     */
    public function addSelected(array|string|int|null $selected, bool $value = false): static
    {
        if (is_array($selected)) {
            // Add multiple selected, only supported when multiple is enabled
            if (!$this->getMultiple()) {
                throw new OutOfBoundsException(tr('Cannot add multiple selected values to this select, it is configured to not allow multiples'));
            }
            // Add each selected to the list
            foreach (Arrays::force($selected) as $selected) {
                $this->addSelected($selected, $value);
            }
        } else {
            // Add each selected to the list
            $this->selected[$selected] = $value;
        }

        return $this;
    }


    /**
     * Sets if the select element allows multiple options to be selected
     *
     * @return bool
     */
    public function getMultiple(): bool
    {
        return (bool) $this->attributes->get('multiple', false);
    }


    /**
     * Clear all multiple class element attributes for option elements
     *
     * @return static
     */
    public function clearOptionClasses(): static
    {
        $this->option_classes = [];

        return $this;
    }


    /**
     * Returns the HTML class element attribute for option elements
     *
     * @return array
     */
    public function getOptionClasses(): array
    {
        return $this->option_classes;
    }


    /**
     * Adds all multiple class element attributes for option elements
     *
     * @param array|string|null $option_classes
     *
     * @return static
     */
    public function setOptionClasses(array|string|null $option_classes): static
    {
        $this->option_classes = [];

        return $this->addOptionClasses($option_classes);
    }


    /**
     * Adds multiple class element attributes for option elements
     *
     * @param array|string|null $option_classes
     *
     * @return static
     */
    public function addOptionClasses(array|string|null $option_classes): static
    {
        foreach (Arrays::force($option_classes, ' ') as $option_class) {
            $this->addOptionClass($option_class);
        }

        return $this;
    }


    /**
     * Adds an class element attribute for option elements
     *
     * @param string $option_class
     *
     * @return static
     */
    public function addOptionClass(string $option_class): static
    {
        $this->option_classes[] = $option_class;

        return $this;
    }


    /**
     * Generates and returns the HTML string for only the select body
     *
     * This will return all HTML WITHOUT the <select> tags around it
     *
     * Return the body HTML for a <select> list
     *
     * @return string|null The body HTML (all <option> tags) for a <select> tag
     * @see \Templates\AdminLte\Html\Components\Input\TemplateInputSelect::render()
     * @see \Templates\AdminLte\Html\Components\Input\TemplateInputSelect::renderHeaders()
     * @see ResourceElement::renderBody()
     * @see ElementInterface::render()
     */
    public function renderBody(): ?string
    {
        $return = null;
        $return .= $this->renderBodyQuery();
        $return .= $this->renderBodyArray();
        if (!$return) {
            return $this->renderBodyEmpty();
        }
        if ($this->none) {
            return '<option' . $this->renderOptionClassString() . $this->renderSelectedString(null, null) . ' value="">' . $this->none . '</option>' . $return;
        }

        return $return;
    }


    /**
     * Generates and returns the HTML string for only the select body for query sources
     *
     * This will return all HTML WITHOUT the <select> tags around it
     *
     * Return the body HTML for a <select> list
     *
     * @return string|null
     * @see \Templates\AdminLte\Html\Components\Input\TemplateInputSelect::render()
     * @see \Templates\AdminLte\Html\Components\Input\TemplateInputSelect::renderHeaders()
     * @see ResourceElement::renderBody()
     * @see ElementInterface::render()
     */
    protected function renderBodyQuery(): ?string
    {
        if (empty($this->source_query)) {
            return null;
        }
        if (empty($this->source)) {
            $this->source = new Iterator();
        }
        while ($row = $this->source_query->fetch()) {
            if ($this->key_column) {
                $key = $row[$this->key_column];
            } else {
                $key = $row[array_key_first($row)];
            }
            if ($this->value_column) {
                $value = $row[$this->value_column];
            } else {
                $value = $row[array_key_last($row)];
            }
            $this->source->add($value, $key);
        }
        $this->source_query = null;

        return null;
//        $return = '';
//
//        if (!$this->source_query) {
//            return '';
//        }
//
//        if (!$this->source_query->rowCount()) {
//            return '';
//        }
//
//        // Get resource data from a query
//        if ($this->auto_select and ($this->source_query->rowCount() == 1)) {
//            // Auto select the only available element
//// :TODO: Implement
//        }
//
//        // Process SQL resource
//        while ($row = $this->source_query->fetch(PDO::FETCH_NUM)) {
//            $this->count++;
//            $option_data = '';
//
//            $key   = $row[array_key_first($row)];
//            $value = $row[array_key_last($row)];
//
//            if ($this->cache) {
//                // Store the data in array
//                if (empty($this->source)) {
//                    $this->source = new Iterator();
//                }
//
//                $this->source->add($value, $key);
//            }
//
//            if (!$key) {
//                // To avoid select problems with "none" entries, empty id column values are not allowed
//                Log::warning(tr('Dropping result ":count" without key from source query ":query"', [
//                    ':count' => $this->count,
//                    ':query' => $this->source_query->queryString
//                ]));
//                continue;
//            }
//
//            // Add data- in this option?
//            if (array_key_exists($key, $this->source_data)) {
//                foreach ($this->source_data as $data_key => $data_value) {
//                    $option_data .= ' data-' . $data_key . '="' . $data_value . '"';
//                }
//            }
//
//            $return .= '<option' . $this->buildOptionClassString() . $this->buildSelectedString($key) . ' value="' . htmlspecialchars((string) $key) . '"' . $option_data . '>' . htmlentities((string) $value) . '</option>';
//        }
//
//        if ($this->cache) {
//            $this->source_query = null;
//        }
//
//        return $return;
    }


    /**
     * Generates and returns the HTML string for only the select body for array sources
     *
     * This will return all HTML WITHOUT the <select> tags around it
     *
     * Return the body HTML for a <select> list
     *
     * @return string|null The body HTML (all <option> tags) for a <select> tag
     * @see \Templates\AdminLte\Html\Components\Input\TemplateInputSelect::render()
     * @see \Templates\AdminLte\Html\Components\Input\TemplateInputSelect::renderHeaders()
     * @see ResourceElement::renderBody()
     * @see ElementInterface::render()
     */
    protected function renderBodyArray(): ?string
    {
        if (!$this->source) {
            return null;
        }
        $return = '';
        if ($this->auto_select and ((count($this->source) == 1) and !$this->none)) {
            // Auto select the only available element
            // TODO implement
        }
        // Process array resource
        foreach ($this->source as $key => $value) {
            $this->count++;
            $option_data = '';
            // Add data- in this option?
            if (array_key_exists($key, $this->source_data)) {
                foreach ($this->source_data as $data_key => $data_value) {
                    $option_data = ' data-' . $data_key . '="' . $data_value . '"';
                }
            }
            if (!is_scalar($value)) {
                if (!($value instanceof Stringable)) {
                    if (!is_array($value)) {
                        throw OutOfBoundsException::new(tr('The specified select source array is invalid. Format should be [key => value, key => value, ...]'))
                                                  ->addData([
                                                      ':first_row_key'   => $key,
                                                      ':first_row_value' => $value,
                                                      ':value_column'    => $this->value_column,
                                                      ':source'          => $this->source,
                                                  ]);
                    }
                    if (!$this->value_column) {
                        throw OutOfBoundsException::new(tr('The specified select source array contains array values, but no value column was specified'))
                                                  ->addData($this->source);
                    }
                    try {
                        $value = $value[$this->value_column];

                    } catch (Throwable $e) {
                        throw OutOfBoundsException::new(tr('Failed to build select body because the data row does not contain the specified value column ":column"', [
                            ':column' => $this->value_column,
                        ]))
                                                  ->setData([
                                                      'value'        => $value,
                                                      'value_column' => $this->value_column,
                                                  ]);
                    }
                }
                // So value is a stringable object. Force value to be a string
                $value = (string) $value;
            }
            $return .= '<option' . $this->renderOptionClassString() . $this->renderSelectedString($key, $value) . ' value="' . htmlspecialchars((string) $key) . '"' . $option_data . '>' . htmlentities((string) $value) . '</option>';
        }

        return $return;
    }


    /**
     * Builds and returns the class string
     *
     * @return string|null
     */
    protected function renderOptionClassString(): ?string
    {
        $option_class = $this->getOptionClass();
        if ($option_class) {
            return ' class="' . $option_class . '"';
        }

        return null;
    }


    /**
     * Returns the HTML class element attribute
     *
     * @return string|null
     */
    public function getOptionClass(): ?string
    {
        if (!$this->option_class) {
            $this->option_class = implode(' ', $this->option_classes);
        }

        return $this->option_class;
    }


    /**
     * Returns the " selected" string that can be injected into <options> elements if the element value is selected
     *
     * @param string|int|null $key
     * @param string|int|null $value
     *
     * @return string|null
     */
    protected function renderSelectedString(string|int|null $key, string|int|null $value): ?string
    {
        // Does the key match?
        if (array_key_exists($key, $this->selected)) {
            // If $this->selected[$value] is false, it means it's a key
            return ($this->selected[$key] ? null : ' selected');
        }
        // Does the value match?
        if (array_key_exists($value, $this->selected)) {
            // If $this->selected[$value] is true, it means it's a value
            return ($this->selected[$value] ? ' selected' : null);
        }

        return null;
    }


    /**
     * Render an <option> for "this select has no data and is empty"
     *
     * @return string|null
     */
    protected function renderBodyEmpty(): ?string
    {
        // No content (other than maybe the "none available" entry) was added
        if ($this->empty) {
            return '<option' . $this->renderOptionClassString() . ' selected value="">' . $this->empty . '</option>';
        }

        return null;
    }
}
