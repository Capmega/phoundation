<?php

namespace Phoundation\Web\Http\Html\Components\Input\Interfaces;


use Phoundation\Web\Http\Html\Components\Interfaces\ResourceElementInterface;


/**
 * Class Select
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
interface SelectInterface extends ResourceElementInterface
{
    /**
     * Sets if the select element allows multiple options to be selected
     *
     * @param bool $multiple
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
     * Sets if there is only one option, it should automatically be selected
     *
     * @param bool $auto_select
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
     * @see \Templates\AdminLte\Html\Components\Input\InputSelect::setAutoSelect()
     */
    public function enableAutoSelect(): static;

    /**
     * Disables auto select
     *
     * @return static
     * @see \Templates\AdminLte\Html\Components\Input\InputSelect::setAutoSelect()
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
     * @return static
     */
    public function setSelected(array|string|int|null $selected = null): static;

    /**
     * Adds a single or multiple selected options
     *
     * @param array|string|int|null $selected
     * @return static
     */
    public function addSelected(array|string|int|null $selected): static;

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
     * @return static
     */
    public function setOptionClasses(array|string|null $option_classes): static;

    /**
     * Adds multiple class element attributes for option elements
     *
     * @param array|string|null $option_classes
     * @return static
     */
    public function addOptionClasses(array|string|null $option_classes): static;

    /**
     * Adds an class element attribute for option elements
     *
     * @param string $option_class
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
     * @see \Templates\AdminLte\Html\Components\Input\InputSelect::render()
     * @see \Templates\AdminLte\Html\Components\Input\InputSelect::renderHeaders()
     * @see ResourceElement::renderBody()
     * @see ElementInterface::render()
     */
    public function renderBody(): ?string;
}