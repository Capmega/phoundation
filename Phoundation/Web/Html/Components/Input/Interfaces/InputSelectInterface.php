<?php

/**
 * interface InputSelectInterface
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input\Interfaces;

use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonInterface;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonsInterface;
use Phoundation\Web\Html\Components\Interfaces\ElementInterface;
use Phoundation\Web\Html\Components\Interfaces\ResourceElementInterface;
use Phoundation\Web\Html\Components\ResourceElement;

interface InputSelectInterface extends ResourceElementInterface
{
    /**
     * Sets if the select element allows multiple options to be selected
     *
     * @param bool $multiple
     *
     * @return static
     */
    public function setMultiple(bool $multiple): static;


    /**
     * Sets if the select element allows multiple options to be selected
     *
     * @return bool
     */
    public function getMultiple(): bool;


    /**
     * Returns the auto complete setting
     *
     * @return bool
     */
    public function getAutoComplete(): bool;


    /**
     * Sets the auto complete setting
     *
     * @param bool $auto_complete
     *
     * @return static
     */
    public function setAutoComplete(bool $auto_complete): static;


    /**
     * Sets if there is only one option, it should automatically be selected
     *
     * @param bool $auto_select
     *
     * @return static
     */
    public function setAutoSelect(bool $auto_select): static;


    /**
     * Returns if there is only one option, it should automatically be selected
     *
     * @return bool
     */
    public function getAutoSelect(): bool;


    /**
     * Enables auto select
     *
     * @return static
     * @see \Templates\Phoundation\AdminLte\Html\Components\Input\TemplateInputSelect::setAutoSelect()
     */
    public function enableAutoSelect(): static;


    /**
     * Disables auto select
     *
     * @return static
     * @see \Templates\Phoundation\AdminLte\Html\Components\Input\TemplateInputSelect::setAutoSelect()
     */
    public function disableAutoSelect(): static;


    /**
     * Clear multiple selected options
     *
     * @return static
     */
    public function clearSelected(): static;


    /**
     * Sets multiple selected options
     *
     * @param array|string|int|null $selected
     * @param bool                  $value
     *
     * @return static
     */
    public function setSelected(array|string|int|null $selected = null, bool $value = false): static;


    /**
     * Adds a single or multiple selected options
     *
     * @param array|string|int|null $selected
     * @param bool                  $value
     *
     * @return static
     */
    public function addSelected(array|string|int|null $selected, bool $value = false): static;


    /**
     * Returns the selected option(s)
     *
     * @return array|string|int|null
     */
    public function getSelected(): array|string|int|null;


    /**
     * Clear all multiple class element attributes for option elements
     *
     * @return static
     */
    public function clearOptionClasses(): static;


    /**
     * Adds all multiple class element attributes for option elements
     *
     * @param array|string|null $option_classes
     *
     * @return static
     */
    public function setOptionClasses(array|string|null $option_classes): static;


    /**
     * Adds multiple class element attributes for option elements
     *
     * @param array|string|null $option_classes
     *
     * @return static
     */
    public function addOptionClasses(array|string|null $option_classes): static;


    /**
     * Adds an class element attribute for option elements
     *
     * @param string $option_class
     *
     * @return static
     */
    public function addOptionClass(string $option_class): static;


    /**
     * Returns the HTML class element attribute for option elements
     *
     * @return array
     */
    public function getOptionClasses(): array;


    /**
     * Returns the HTML class element attribute
     *
     * @return string|null
     */
    public function getOptionClass(): ?string;


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
    public function renderBody(): ?string;


    /**
     * Returns if the select element has a search
     *
     * @return bool
     */
    public function getSearch(): bool;


    /**
     * Sets if the select element has a search
     *
     * @param bool $search
     *
     * @return static
     */
    public function setSearch(bool $search): static;


    /**
     * Returns if the select element has a clear_button
     *
     * @return bool
     */
    public function getClearButton(): bool;


    /**
     * Sets if the select element has a clear_button
     *
     * @param bool $clear_button
     *
     * @return static
     */
    public function setClearButton(bool $clear_button): static;


    /**
     * Returns if the select element has custom_content
     *
     * @return string|null
     */
    public function getCustomContent(): ?string;


    /**
     * Sets if the select element has custom_content
     *
     * @param string|null $custom_content
     *
     * @return static
     */
    public function setCustomContent(?string $custom_content): static;

    /**
     * Returns if this input element has after buttons
     *
     * @return bool
     */
    public function hasAfterButtons(): bool;

    /**
     * Returns the modal after_buttons
     *
     * @return ButtonsInterface|null
     */
    public function getAfterButtons(): ?ButtonsInterface;

    /**
     * Sets the modal after_buttons
     *
     * @param ButtonsInterface|null $after_buttons
     *
     * @return static
     */
    public function setAfterButtons(?ButtonsInterface $after_buttons): static;

    /**
     * Sets the modal after_buttons
     *
     * @param ButtonInterface|null $button
     *
     * @return static
     */
    public function addAfterButton(?ButtonInterface $button): static;

    /**
     * Returns if this input element has before buttons
     *
     * @return bool
     */
    public function hasBeforeButtons(): bool;

    /**
     * Returns the modal before_buttons
     *
     * @return ButtonsInterface|null
     */
    public function getBeforeButtons(): ?ButtonsInterface;

    /**
     * Sets the modal before_buttons
     *
     * @param ButtonsInterface|null $before_buttons
     *
     * @return static
     */
    public function setBeforeButtons(?ButtonsInterface $before_buttons): static;

    /**
     * Sets the modal before_buttons
     *
     * @param ButtonInterface|null $button
     *
     * @return static
     */
    public function addBeforeButton(?ButtonInterface $button): static;

    /**
     * Returns if this object renders a list of checkboxes instead of a select drop-down
     *
     * @return bool
     */
    public function getRenderCheckboxes(): bool;

    /**
     * Sets if this object renders a list of checkboxes instead of a select drop-down
     *
     * @param bool $render_checkboxes
     *
     * @return static
     */
    public function setRenderCheckboxes(bool $render_checkboxes): static;
}
