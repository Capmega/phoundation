<?php

namespace Phoundation\Web\Http\Html\Components\Input;

use PDO;
use Phoundation\Core\Arrays;
use Phoundation\Core\Log\Log;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Components\ResourceElement;
use Phoundation\Web\Http\Html\Exception\HtmlException;

/**
 * Class Select
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Select extends ResourceElement
{
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
     * Select constructor
     */
    public function __construct()
    {
        parent::__construct();
        parent::setElement('select');
    }


    /**
     * Sets if the select element allows multiple options to be selected
     *
     * @param bool $multiple
     * @return static
     */
    public function setMultiple(bool $multiple): static
    {
        $this->attributes['multiple'] = null;
        return $this;
    }


    /**
     * Sets if the select element allows multiple options to be selected
     *
     * @return bool
     */
    public function getMultiple(): bool
    {
        return (bool) array_key_exists('multiple', $this->attributes);
    }


    /**
     * Sets if there is only one option, it should automatically be selected
     *
     * @param bool $auto_select
     * @return static
     */
    public function setAutoSelect(bool $auto_select): static
    {
        $this->auto_select = $auto_select;
        return $this;
    }


    /**
     * Returns if there is only one option, it should automatically be selected
     *
     * @return bool
     */
    public function getAutoSelect(): bool
    {
        return (bool) $this->auto_select;
    }


    /**
     * Enables auto select
     *
     * @return static
     * @see \Templates\AdminLte\Html\Components\Input\Select::setAutoSelect()
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
     * @see \Templates\AdminLte\Html\Components\Input\Select::setAutoSelect()
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
     * Sets multiple selected options
     *
     * @param array|string|int|null $selected
     * @return static
     */
    public function setSelected(array|string|int|null $selected = null): static
    {
        $this->selected = [];
        return $this->addSelected($selected);
    }


    /**
     * Adds a single or multiple selected options
     *
     * @param array|string|int|null $selected
     * @return static
     */
    public function addSelected(array|string|int|null $selected): static
    {
        if (is_array($selected)) {
            // Add multiple selected, only supported when multiple is enabled
            if (!$this->getMultiple()) {
                throw new OutOfBoundsException(tr('Cannot add multiple selected values to this select, it is configured to not allow multiples'));
            }

            // Add each selected to the list
            foreach (Arrays::force($selected) as $selected) {
                $this->addSelected($selected);
            }
        } else {
            // Add each selected to the list
            $this->selected[$selected] = true;
        }

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
     * Adds all multiple class element attributes for option elements
     *
     * @param array|string|null $option_classes
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
     * @return static
     */
    public function addOptionClass(string $option_class): static
    {
        $this->option_classes[] = $option_class;
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
     * Generates and returns the HTML string for only the select body
     *
     * This will return all HTML WITHOUT the <select> tags around it
     *
     * Return the body HTML for a <select> list
     *
     * @return string|null The body HTML (all <option> tags) for a <select> tag
     * @see \Templates\AdminLte\Html\Components\Input\Select::render()
     * @see \Templates\AdminLte\Html\Components\Input\Select::renderHeaders()
     * @see ResourceElement::renderBody()
     * @see InterfaceElement::render()
     */
    public function renderBody(): ?string
    {
        $return = null;
        $none   = null;

        if (($this->source_array === null) and ($this->source_query === null)) {
            throw new HtmlException(tr('No source specified'));
        }

        if ($this->none) {
            $none = '<option' . $this->buildOptionClassString() . $this->buildSelectedString(null) . ' value="">' . $this->none . '</option>';
        }

        if ($this->source_query) {
            $return .= $this->renderBodyQuery();
        } elseif ($this->source_array) {
            $return .= $this->renderBodyArray();
        }

        if (!$return) {
            return $this->renderBodyEmpty();
        }

        return $none . $return;
    }


    /**
     * Generates and returns the HTML string for only the select body for array sources
     *
     * This will return all HTML WITHOUT the <select> tags around it
     *
     * Return the body HTML for a <select> list
     *
     * @return string|null The body HTML (all <option> tags) for a <select> tag
     *@see \Templates\AdminLte\Html\Components\Input\Select::render()
     * @see \Templates\AdminLte\Html\Components\Input\Select::renderHeaders()
     * @see ResourceElement::renderBody()
     * @see InterfaceElement::render()
     */
    protected function renderBodyArray(): ?string
    {
        if (!$this->source_array) {
            return null;
        }

        $return = '';

        if ($this->auto_select and ((count($this->source_array) == 1) and !$this->none)) {
            // Auto select the only available element
            // TODO implement
        }

        // Process array resource
        foreach ($this->source_array as $key => $value) {
            $this->count++;
            $option_data = '';

            // Add data- in this option?
            if (array_key_exists($key, $this->source_data)) {
                foreach ($this->source_data as $data_key => $data_value) {
                    $option_data = ' data-' . $data_key . '="' . $data_value . '"';
                }
            }

            if (!is_scalar($value)) {
                throw new OutOfBoundsException(tr('The specified select source array is invalid. Format should be [key => value, key => value, ...]'));
            }

            $return .= '<option' . $this->buildOptionClassString() . $this->buildSelectedString($key) . ' value="' . htmlentities($key) . '"' . $option_data . '>' . htmlentities($value) . '</option>';
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
     * @return string|null The body HTML (all <option> tags) for a <select> tag
     * @see \Templates\AdminLte\Html\Components\Input\Select::render()
     * @see \Templates\AdminLte\Html\Components\Input\Select::renderHeaders()
     * @see ResourceElement::renderBody()
     * @see InterfaceElement::render()
     */
    protected function renderBodyQuery(): ?string
    {
        $return = '';

        if (!$this->source_query) {
            return '';
        }

        if (!$this->source_query->rowCount()) {
            return '';
        }

        // Get resource data from a query
        if ($this->auto_select and ($this->source_query->rowCount() == 1)) {
            // Auto select the only available element
// :TODO: Implement
        }

        // Process SQL resource
        while ($row = $this->source_query->fetch(PDO::FETCH_NUM)) {
            $this->count++;
            $option_data = '';

            $key   = $row[array_key_first($row)];
            $value = $row[array_key_last($row)];

            if ($this->cache) {
                // Store the data in array
                $this->source_array[$key] = $value;
            }

            if (!$key) {
                // To avoid select problems with "none" entries, empty id column values are not allowed
                Log::warning(tr('Dropping result ":count" without key from source query ":query"', [
                    ':count' => $this->count,
                    ':query' => $this->source_query->queryString
                ]));
                continue;
            }

            // Add data- in this option?
            if (array_key_exists($key, $this->source_data)) {
                foreach ($this->source_data as $data_key => $data_value) {
                    $option_data .= ' data-' . $data_key . '="' . $data_value . '"';
                }
            }

            $return .= '<option' . $this->buildOptionClassString() . $this->buildSelectedString($key) . ' value="' . htmlentities($key) . '"' . $option_data . '>' . htmlentities($value) . '</option>';
        }

        if ($this->cache) {
            $this->source_query = null;
        }

        return $return;
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
            return '<option' . $this->buildOptionClassString() . ' selected value="">' . $this->empty . '</option>';
        }

        return null;
    }


    /**
     * Builds and returns the class string
     *
     * @return string|null
     */
    protected function buildOptionClassString(): ?string
    {
        $option_class = $this->getOptionClass();

        if ($option_class) {
            return ' class="' . $option_class . '"';
        }

        return null;
    }


    /**
     * Returns the " selected" string that can be injected into <options> elements if the element value is selected
     *
     * @param string|int|null $value
     * @return string|null
     */
    protected function buildSelectedString(string|int|null $value): ?string
    {
        return (array_key_exists($value, $this->selected) ? ' selected' : null);
    }
}
