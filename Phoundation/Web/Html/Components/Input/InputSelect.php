<?php

/**
 * class InputSelect
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Security\Incidents\EnumSeverity;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Input\Interfaces\InputInterface;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;
use Phoundation\Web\Html\Components\Input\Interfaces\SelectedInterface;
use Phoundation\Web\Html\Components\Interfaces\ElementInterface;
use Phoundation\Web\Html\Components\ResourceElement;
use Phoundation\Web\Html\Traits\TraitBeforeAfterContent;
use Stringable;
use Throwable;


class InputSelect extends ResourceElement implements InputSelectInterface, InputInterface, SelectedInterface
{
    use TraitBeforeAfterContent;


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
     * Tracks if this object renders a list of checkboxes instead of a select drop-down
     *
     * @var bool $render_checkboxes
     */
    protected bool $render_checkboxes = false;


    /**
     * Select constructor
     *
     * @param IteratorInterface|array|null $source
     */
    public function __construct(IteratorInterface|array|null $source = null)
    {
        parent::__construct($source);
        $this->setElement('select');
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
     * Returns if this object renders a list of checkboxes instead of a select drop-down
     *
     * @return bool
     */
    public function getRenderCheckboxes(): bool
    {
        return $this->render_checkboxes;
    }


    /**
     * Sets if this object renders a list of checkboxes instead of a select drop-down
     *
     * @param bool $render_checkboxes
     *
     * @return static
     */
    public function setRenderCheckboxes(bool $render_checkboxes): static
    {
        $this->render_checkboxes = $render_checkboxes;
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
     * Set the HTML readonly element attribute
     *
     * Enabling readonly on a select element will also enable disabled!
     *
     * @param bool              $readonly
     * @param bool|null         $set_disabled
     * @param string|false|null $title
     *
     * @return static
     */
    public function setReadonly(bool $readonly, ?bool $set_disabled = true, string|false|null $title = null): static
    {
        return parent::setReadonly($readonly, $set_disabled, $title);
    }


    /**
     * Set the HTML disabled element attribute
     *
     * @param bool              $disabled
     * @param bool|null         $set_readonly
     * @param string|false|null $title
     *
     * @return static
     */
    public function setDisabled(bool $disabled, ?bool $set_readonly = true, string|false|null $title = null): static
    {
        return parent::setDisabled($disabled, $set_readonly, $title);
    }


    /**
     * Sets if the select element allows multiple options to be selected
     *
     * @return bool
     */
    public function getMultiple(): bool
    {
        return (bool) $this->_attributes->get('multiple');
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
     * Returns how many rows of the select will be displayed
     *
     * @return int|null
     */
    public function getSize(): ?int
    {
        return get_null((int) $this->_attributes->get('size'));
    }


    /**
     * Sets how many rows of the select will be displayed
     *
     * @param int|null $size
     *
     * @return static
     */
    public function setSize(?int $size): static
    {
        return $this->setAttribute($size, 'size');
    }


    /**
     * Returns if the select element has a search
     *
     * @return bool
     */
    public function getSearch(): bool
    {
        return (bool) $this->_attributes->get('search');
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
        return (bool) $this->_attributes->get('clear_button', exception: false);
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
        return $this->_attributes->get('custom_content');
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
        return Strings::toBoolean($this->_attributes->get('autocomplete'));
    }


    /**
     * Sets the auto complete setting
     *
     * @param bool $auto_complete
     *
     * @return static
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
     * @see \Templates\Phoundation\AdminLte\Html\Components\Input\TemplateInputSelect::setAutoSelect()
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
     * @see \Templates\Phoundation\AdminLte\Html\Components\Input\TemplateInputSelect::setAutoSelect()
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
     * @return array|string|float|int|null
     */
    public function getSelected(): array|string|float|int|null
    {
        return $this->selected;
    }


    /**
     * Sets multiple selected options
     *
     * @param IteratorInterface|array|string|float|int|null $selected
     * @param bool                                          $value
     *
     * @return static
     */
    public function setSelected(IteratorInterface|array|string|float|int|null $selected = null, bool $value = false): static
    {
        $this->selected = [];
        return $this->addSelected($selected, $value);
    }


    /**
     * Adds a single or multiple selected options
     *
     * @param IteratorInterface|array|string|float|int|null $selected
     * @param bool                                          $value
     *
     * @return static
     */
    public function addSelected(IteratorInterface|array|string|float|int|null $selected, bool $value = false): static
    {
        if (is_array($selected) or ($selected instanceof IteratorInterface)) {
            // Add multiple selected, only supported when multiple is enabled
            if (!$this->getMultiple()) {
                throw new OutOfBoundsException(tr('Cannot add multiple selected values to this select, it is configured to not allow multiples'));
            }

            // Add each selected to the list
            foreach (Arrays::force($selected) as $selected) {
                $this->addSelected($selected, $value);
            }

        } elseif ($selected !== null) {
            // Add each selected to the list
            $this->selected[$selected] = $value;
        }

        return $this;
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
     * Validates that the specified selected entries exist in the source data
     *
     * @param bool $throw_validation_exception
     *
     * @return static
     */
    public function validateSelected(bool $throw_validation_exception = false): static
    {
        if (empty($this->selected)) {
            // No validation required, there is nothing selected
            return $this;
        }

        if (config()->getBoolean('security.validation.select.disabled', false)) {
            // Validation of <select> component source is disabled
            return $this;
        }

        $results = array_intersect_key($this->selected, $this->source);
        $diff    = array_diff($this->selected, $results);

        if (empty($diff)) {
            // The selected key(s) exist in the source, we are good
            return $this;
        }

        // The selected key(s) do not (all) exist in the source. Either the source is empty or simply does not contain the specified key. Register an incident
        return $this->selectedNotInSource($throw_validation_exception, $diff);
    }


    /**
     * Handles the issue of one or more selected items not existing in the source
     *
     * @param bool  $throw_validation_exception
     * @param array $diff
     *
     * @return static
     */
    protected function selectedNotInSource(bool $throw_validation_exception, array $diff): static
    {
        if ($this->source) {
            $i = Incident::new()
                         ->setBody(tr('The selected keys(s) ":values" in ":class" object ":name" do not exist in the InputSelect source', [
                             ':class'  => static::class,
                             ':name'   => $this->getName() ?? ('id: ' . $this->getId()),
                             ':values' => array_keys($diff),
                         ]));

        } else {
            $i = Incident::new()
                         ->setBody(tr('The ":class" InputSelect object ":name" has an empty source, but does have selected keys(s) ":values"', [
                             ':class'  => static::class,
                             ':name'   => $this->getName() ?? ('id: ' . $this->getId()),
                             ':values' => array_keys($diff),
                         ]));
        }

        $i->setType('invalid-data')
          ->setSeverity(EnumSeverity::low)
          ->setTitle(tr('InputSelect selected keys do not exist in source'))
          ->setData([
              ':selected' => $this->selected,
              ':source'   => $this->source,
              ':diff'     => $diff,
          ])
          ->setLog(9)
          ->setNotifyRoles('developer')
          ->save();

        if ($throw_validation_exception) {
            $i->throw(ValidationFailedException::class, true);
        }

        $this->selected = [];
        return $this;
    }


    /**
     * @inheritDoc
     */
    public function render(): ?string
    {
        $this->executeQuery()
             ->validateSelected();

        if ($this->render_checkboxes) {
            $render = '';

            if ($this->getMultiple()) {
                // Render checkboxes instead of a <select> component
                foreach ($this->source as $key => $value) {
                    $render .= InputCheckbox::new()
                                            ->setName($this->name)
                                            ->setId($this->name . '_' . strtolower((string) $key), false)
                                            ->setValue($key)
                                            ->setLabel($value)
                                            ->setInline(false)
                                            ->setReadonly($this->getReadonly())
                                            ->setDisabled($this->getDisabled())
                                            ->render();
                }

            } else {
                // Render radiobuttons instead of a <select> component
                foreach ($this->source as $key => $value) {
                    $input_radio = InputRadio::new()
                                             ->setName($this->name)
                                             ->setId($this->name . '_' . strtolower((string) $key), false)
                                             ->setValue($key)
                                             ->setLabel($value)
                                             ->setReadonly($this->getReadonly())
                                             ->setDisabled($this->getDisabled())
                                             ->setInline(false);

                    if (in_array($key, array_keys($this->selected))) {
                        $input_radio->setChecked(true);
                    }

                    $render .= $input_radio->render();
                }
            }

            return '<div>' . $render . '</div>';
        }

        return parent::render();
    }


    /**
     * Generates and returns the HTML string for only the select body
     *
     * This will return all HTML WITHOUT the <select> tags around it
     *
     * Return the body HTML for a <select> list
     *
     * @return string|null The body HTML (all <option> tags) for a <select> tag
     * @see \Templates\Phoundation\AdminLte\Html\Components\Input\TemplateInputSelect::render()
     * @see \Templates\Phoundation\AdminLte\Html\Components\Input\TemplateInputSelect::renderHeaders()
     * @see ResourceElement::renderBody()
     * @see ElementInterface::render()
     */
    public function renderBody(): ?string
    {
        $this->executeQuery();

        $return = $this->renderBodyArray();

        if (!$return) {
            return $this->renderBodyEmpty();
        }

        if ($this->not_selected_label) {
            return '<option' . $this->renderOptionClassString() . $this->renderSelectedString(null, null) . ' value="">' . $this->not_selected_label . '</option>' . $return;
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
     * @see    \Templates\Phoundation\AdminLte\Html\Components\Input\TemplateInputSelect::render()
     * @see    \Templates\Phoundation\AdminLte\Html\Components\Input\TemplateInputSelect::renderHeaders()
     * @see    ResourceElement::renderBody()
     * @see    ElementInterface::render()
     */
    protected function renderBodyQuery(): ?string
    {
        return null;
    }


    /**
     * Generates and returns the HTML string for only the select body for query sources
     *
     * This will return all HTML WITHOUT the <select> tags around it
     *
     * Return the body HTML for a <select> list
     *
     * @return static
     * @todo   Ensure support for QueryBuilder works correctly!
     */
    protected function executeQuery(): static
    {
        if ($this->source_query) {
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

                $this->source[$key] = $value;
            }
        }

        $this->source_query = null;

        return $this;
    }


    /**
     * Generates and returns the HTML string for only the select body for array sources
     *
     * This will return all HTML WITHOUT the <select> tags around it
     *
     * Return the body HTML for a <select> list
     *
     * @return string|null The body HTML (all <option> tags) for a <select> tag
     *
     * @see \Templates\Phoundation\AdminLte\Html\Components\Input\TemplateInputSelect::render()
     * @see \Templates\Phoundation\AdminLte\Html\Components\Input\TemplateInputSelect::renderHeaders()
     * @see ResourceElement::renderBody()
     * @see ElementInterface::render()
     */
    protected function renderBodyArray(): ?string
    {
        if (!$this->source) {
            return null;
        }

        $return = '';

        if ($this->auto_select and ((count($this->source) == 1) and !$this->not_selected_label)) {
            // Auto select the only available element
            // TODO implement
        }

        // Process array resource
        foreach ($this->source as $key => $value) {
            $this->count++;
            $option_data = '';

            // Add data- in this option?
            if (array_key_exists($key, $this->data_source)) {
                foreach ($this->data_source as $data_key => $data_value) {
                    $option_data = ' data-' . $data_key . '="' . $data_value . '"';
                }
            }

            if ($value) {
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
                            throw OutOfBoundsException::new(tr('The specified ":id" select source array contains array values, but no value column was specified', [
                                ':id' => $this->getId() . ' / ' . $this->getName(),
                            ]))->addData($this->source);
                        }

                        try {
                            $value = $value[$this->value_column];

                        } catch (Throwable $e) {
                            throw OutOfBoundsException::new(tr('Failed to build select body because the data row does not contain the specified value column ":column"', [
                                ':column' => $this->value_column,
                            ]))->setData([
                                'value'        => $value,
                                'value_column' => $this->value_column,
                            ]);
                        }
                    }

                    // So value is a stringable object. Force value to be a string
                    $value = (string) $value;
                }

            } else {
                Log::warning(ts('Encountered empty value with key ":key" in following select source array, not displaying entry', [
                    ':key' => $key,
                ]));
                Log::printr(Arrays::force($this->source), echo_header: false);
                continue;
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
            // If $this->selected[$value] is false, it means it is a key
            return ($this->selected[$key] ? null : ' selected');
        }

        // Does the value match?
        if (array_key_exists($value, $this->selected)) {
            // If $this->selected[$value] is true, it means it is a value
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
        if ($this->component_empty_label) {
            return '<option' . $this->renderOptionClassString() . ' selected value="">' . $this->component_empty_label . '</option>';
        }

        return null;
    }


    /**
     * Set the DataEntry Definition on this element
     *
     * @param DefinitionInterface|null $_definition
     *
     * @return static
     */
    public function setDefinitionObject(?DefinitionInterface $_definition): static
    {
        return parent::setDefinitionObject($_definition)
                     ->setRequired($_definition->getRequired(false));
    }
}
