<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;


/**
 * interface ElementsBlockInterface
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
interface ElementsBlockInterface extends IteratorInterface
{
    /**
     * Sets the content of the element to display
     *
     * @param bool $use_form
     * @return static
     */
    public function useForm(bool $use_form): static;

    /**
     * Returns the form of this objects block
     *
     * @return FormInterface|null
     */
    public function getForm(): ?FormInterface;

    /**
     * Returns the form of this objects block
     *
     * @param FormInterface|null $form
     * @return static
     */
    public function setForm(?FormInterface $form): static;

    /**
     * If set true, when this element renders it will only return the contents
     *
     * @param bool $enable
     * @return $this
     */
    public function setRenderContentsOnly(bool $enable): static;

    /**
     * Returns if this element renders it will only return the contents
     *
     * @return bool
     */
    public function getRenderContentsOnly(): bool;

    /**
     * Renders and returns the HTML for this object using the template renderer if available
     *
     * @note Templates work as follows: Any component that renders HTML must be in a Html/ directory, either in a
     *       Phoundation library, or in a Plugins library. The path of the component, starting from Html/ is the path
     *       that this method will search for in the Template. If the same path section is found then that file will
     *       render the HTML for the component. For example: Plugins\Example\Section\Html\Components\Input\InputText
     *       with Template AdminLte will be rendered by Templates\AdminLte\Html\Components\Input\InputText
     *
     * @return string|null
     * @see ElementInterface::render()
     */
    public function render(): ?string;

    /**
     * Returns if this FlashMessages object has rendered HTML or not
     *
     * @return bool
     */
    public function hasRendered(): bool;
}
