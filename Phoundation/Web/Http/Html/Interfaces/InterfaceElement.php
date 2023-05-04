<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Interfaces;


/**
 * Interface Element
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
interface InterfaceElement extends InterfaceElementAttributes
{
    /**
     * Sets the type of element to display
     *
     * @param string $element
     * @return static
     */
    public function setElement(string $element): static;

    /**
     * Returns the HTML class element attribute
     *
     * @return string
     */
    public function getElement(): string;

    /**
     * Renders and returns the HTML for this object using the template renderer if avaialable
     *
     * @note Templates work as follows: Any component that renders HTML must be in a Html/ directory, either in a
     *       Phoundation library, or in a Plugins library. The path of the component, starting from Html/ is the path
     *       that this method will search for in the Template. If the same path section is found then that file will
     *       render the HTML for the component. For example: Plugins\Example\Section\Html\Components\Input\InputText
     *       with Template AdminLte will be rendered by Templates\AdminLte\Html\Components\Input\InputText
     *
     * @return string|null
     * @see ElementsBlock::render()
     */
    public function render(): ?string;
 }