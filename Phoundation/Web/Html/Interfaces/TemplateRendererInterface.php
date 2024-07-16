<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Interfaces;

use Phoundation\Web\Html\Components\Input\Interfaces\RenderInterface;

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
     * @param RenderInterface|null $component
     *
     * @return static
     */
    public function setComponent(RenderInterface|null $component): static;


    /**
     * Returns the element to be rendered
     *
     * @return RenderInterface|null
     */
    public function getComponent(): RenderInterface|null;


    /**
     * Render and return the HTML for this object
     *
     * @return string|null
     */
    public function render(): ?string;
}