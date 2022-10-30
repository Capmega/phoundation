<?php

namespace Phoundation\Web\Http\Html;

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
     * @var array|string|int|null $selected
     */
    protected array|string|int|null $selected = null;

    /**
     * If set, this select element supports the selection of multiple options
     *
     * @var string|null
     */
    protected ?string $multiple = null;

    /**
     * If set, this select element supports the selection of multiple options
     *
     * @var bool
     */
    protected bool $auto_select = false;



    /**
     * Sets if the select element allows multiple options to be selected
     *
     * @param bool $multiple
     * @return Select
     */
    public function setMultiple(bool $multiple): self
    {
        $this->multiple = ($multiple ? 'multiple' : null);
        return $this;
    }



    /**
     * Sets if the select element allows multiple options to be selected
     *
     * @return bool
     */
    public function getMultiple(): bool
    {
        return (bool) $this->multiple;
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
     * @param array|string|int|null $multiple_selected
     * @return Select
     */
    public function setMultipleSelecteds(array|string|int|null $multiple_selected): self
    {
        if (!$this->multiple) {
            throw new OutOfBoundsException(tr('Cannot add multiple selected values to this select, it is configured to not allow multiples'));
        }

        $this->selected = [];
        return $this->addMultipleSelecteds($multiple_selected);
    }



    /**
     * Adds multiple selected option to a list of options
     *
     * @param array|string|int|null $multiple_selected
     * @return Select
     */
    public function addMultipleSelecteds(array|string|int|null $multiple_selected): self
    {
        if (!$this->multiple) {
            throw new OutOfBoundsException(tr('Cannot add multiple selected values to this select, it is configured to not allow multiples'));
        }

        foreach (Arrays::force($multiple_selected) as $selected) {
            $this->addSelected($selected);
        }

        return $this;
    }



    /**
     * Adds a single selected option to a list of options
     *
     * @param null|int|string $selected
     * @return Select
     */
    public function addSelected(null|int|string $selected): self
    {
        if (!$this->multiple) {
            throw new OutOfBoundsException(tr('Cannot add multiple selected values to this select, it is configured to not allow multiples'));
        }

        $this->selected[] = $selected;
        return $this;
    }



    /**
     * Sets a single selected option
     *
     * @param null|int|string $selected
     * @return Select
     */
    public function setSelected(null|int|string $selected): self
    {
        $this->selected = [$selected];
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
     * Sets the HTML option_class element attribute
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
     * Sets the HTML option_class element attribute
     *
     * @param string|null $option_classes
     * @return Select
     */
    public function addOptionClasses(?string $option_classes): self
    {
        foreach (Arrays::force($option_classes, ' ') as $option_class) {
            $this->addOptionClass($option_class);
        }

        return $this;
    }



    /**
     * Adds an option_class to the HTML option_class element attribute
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
     * Returns the HTML option_class element attribute
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
     * Generates and returns the HTML string for a <select> control
     *
     * @return string
     */
    public function render(): string
    {
        // Render the body
        $body = self::renderBody();

        if (!$body and $this->hide_empty) {
            return '';
        }

        // Render header and return
        return $this->renderHeaders() . $body;
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
        $empty = true;

        if ($this->none) {
            $return = '<option' . $this->buildOptionClassString() . $this->buildSelectedString(null) . ' value="">' . $this->none . '</option>';
        }

        if ($this->source === null) {
            if ($this->source_query === null) {
                throw new HtmlException(tr('No data source specified'));
            } else {
                $empty = (bool) $this->source_query->rowCount();

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
                            $option_data = ' data-' . $key . '="' . $value . '"';
                        }
                    }

                    $return .= '<option' . $this->buildOptionClassString() . $this->buildSelectedString($row[0]) . ' value="' . html_safe($row[0]) . '"' . $option_data . '>' . html_safe($row[1]) . '</option>';
                }
            }
        } else {
            // Get resource data from an array
            $empty = (bool) count($this->source);

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

                $return .= '<option' . $this->buildOptionClassString() . $this->buildSelectedString($key) . ' value="' . html_safe($key) . '"' . $option_data . '>' . html_safe($value) . '</option>';
            }
        }


        if ($empty) {
            // No conent (other than maybe the "none available" entry) was added
            if ($this->empty) {
                $return = '<option' . $this->buildOptionClassString() . ' selected value="">' . $this->empty . '</option>';
            }

            // Return empty body (though possibly with "none" element) so that the Html::select() class can ensure the
            // select box will be disabled
            return $return;
        }

        return $return;
    }



    /**
     * Render the select body
     *
     * @return string
     */
    protected function renderHeaders(): string
    {
        return parent::render();
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
            return ' class="' . $option_class. '"';
        }

        return null;
    }



    /**
     * Returns the " selected" string that can be injected into <options> elements if the element value is selected
     *
     * @param string|int|null $value
     * @param bool $strict If true, a strict comparison will be done. NOTE: Does NOT work for multi_select!
     * @return string|null
     */
    protected function buildSelectedString(string|int|null $value, bool $strict = true): ?string
    {
        if (is_array($this->selected)) {
            $selected = array_key_exists($value, $this->selected);
        } elseif ($strict) {
            // Strict comparison
            $selected = $this->selected === $value;
        } else {
            // Non-strict comparison
            $selected = $this->selected == $value;
        }

        return ($selected ? ' selected' : null);
    }
}
