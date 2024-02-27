<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Interfaces;

use Phoundation\Web\Html\Components\Interfaces\ElementInterface;
use Phoundation\Web\Html\Components\Interfaces\ElementsBlockInterface;


/**
 * Class Renderer
 *
 * This class contains basic template functionalities. All template classes must extend this class!
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
interface TemplateRendererInterface
{
    /**
     * Sets the parent rendering function
     *
     * @param callable $render_function
     * @return static
     */
    public function setParentRenderFunction(callable $render_function): static;

    /**
     * Returns the parent rendering function
     *
     * @return callable
     */
    public function getParentRenderFunction(): callable;

    /**
     * Sets the element to be rendered
     *
     * @param ElementsBlockInterface|ElementInterface $render_object
     * @return static
     */
    public function setRenderobject(ElementsBlockInterface|ElementInterface $render_object): static;

    /**
     * Returns the element to be rendered
     *
     * @return ElementsBlockInterface|ElementInterface
     */
    public function getRenderobject(): ElementsBlockInterface|ElementInterface;

    /**
     * Render and return the HTML for this object
     *
     * @return string|null
     */
    public function render(): ?string;
}