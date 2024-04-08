<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Interfaces;

use Phoundation\Web\Html\Components\Input\Interfaces\RenderInterface;

/**
 * Class Renderer
 *
 * This class contains basic template functionalities. All template classes must extend this class!
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */
interface TemplateRendererInterface
{
    /**
     * Sets the parent rendering function
     *
     * @param callable $render_function
     *
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
     * @param RenderInterface $component
     *
     * @return static
     */
    public function setComponent(RenderInterface $component): static;


    /**
     * Returns the element to be rendered
     *
     * @return RenderInterface
     */
    public function getComponent(): RenderInterface;


    /**
     * Render and return the HTML for this object
     *
     * @return string|null
     */
    public function render(): ?string;
}