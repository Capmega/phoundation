<?php

namespace Phoundation\Web\Http\Html\Elements;

use PDO;
use Phoundation\Core\Arrays;
use Phoundation\Core\Log;
use Phoundation\Exception\OutOfBoundsException;
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
     * @return Select
     */
    public function setMultiple(bool $multiple): self
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
     * @return Select
     */
    public function setAutoSelect(bool $auto_select): self
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
     * @see Select::setAutoSelect()
     * @return Select
     */
    public function enableAutoSelect(): self
    {
        $this->auto_select = true;
        return $this;
    }



    /**
     * Disables auto select
     *
     * @see Select::setAutoSelect()
     * @return Select
     */
    public function disableAutoSelect(): self
    {
        $this->auto_select = false;
        return $this;
    }



    /**
     * Sets multiple selected options
     *
     * @param array|string|int|null $selected
     * @return Select
     */
    public function setSelected(array|string|int|null $selected): self
    {
        $this->selected = [];
        return $this->addSelected($selected);
    }



    /**
     * Adds a single or multiple selected options
     *
     * @param array|string|int $selected
     * @return Select
     */
    public function addSelected(array|string|int $selected): self
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
     * Adds all multiple class element attributes for option elements
     *
     * @param array|string|null $option_classes
     * @return Select
     */
    public function setOptionClasses(array|string|null $option_classes): self
    {
        $this->option_classes = [];
        return $this->addOptionClasses($option_classes);
    }



    /**
     * Adds multiple class element attributes for option elements
     *
     * @param array|string|null $option_classes
     * @return Select
     */
    public function addOptionClasses(array|string|null $option_classes): self
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
     * @return Select
     */
    public function addOptionClass(string $option_class): self
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
     * @see Element::render()
     * @see Select::render()
     * @see Select::renderHeaders()
     * @see ResourceElement::renderBody()
     * @return string The body HTML (all <option> tags) for a <select> tag
     */
    public function renderBody(): string
    {
        $return = '';
        $empty  = true;

        if (($this->source === null) and ($this->source_query === null)) {
            throw new HtmlException(tr('No source specified'));
        }

        if ($this->none) {
            $return = '<option' . $this->buildOptionClassString() . $this->buildSelectedString(null) . ' value="">' . $this->none . '</option>';
        }

        $return .= $this->renderBodyQuery();
        $return .= $this->renderBodyArray();

        if (!$return) {
            $return = $this->renderBodyEmpty();
        }

        return $return;
    }



    /**
     * Generates and returns the HTML string for only the select body for array sources
     *
     * This will return all HTML WITHOUT the <select> tags around it
     *
     * Return the body HTML for a <select> list
     *
     * @see Element::render()
     * @see Select::render()
     * @see Select::renderHeaders()
     * @see ResourceElement::renderBody()
     * @return string The body HTML (all <option> tags) for a <select> tag
     */
    protected function renderBodyArray(): string
    {
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
     * @see Element::render()
     * @see Select::render()
     * @see Select::renderHeaders()
     * @see ResourceElement::renderBody()
     * @return string The body HTML (all <option> tags) for a <select> tag
     */
    protected function renderBodyQuery(): string
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

            if (!$row[0]) {
                // To avoid select problems with "none" entries, empty id column values are not allowed
                Log::warning(tr('Dropping result ":count" without key from source query ":query"', [
                    ':count' => $this->count,
                    ':query' => $this->source_query->queryString
                ]));
                continue;
            }

            // Add data- in this option?
            if (array_key_exists($row[0], $this->source_data)) {
                foreach ($this->source_data as $key => $value) {
                    $option_data .= ' data-' . $key . '="' . $value . '"';
                }
            }

            $return .= '<option' . $this->buildOptionClassString() . $this->buildSelectedString($row[0]) . ' value="' . htmlentities($row[0]) . '"' . $option_data . '>' . htmlentities($row[1]) . '</option>';
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
