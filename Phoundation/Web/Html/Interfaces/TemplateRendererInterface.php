<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Interfaces;

use Phoundation\Web\Html\Components\Interfaces\ComponentInterface;


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
     * @param ComponentInterface|null $component
     *
     * @return static
     */
    public function setComponentObject(ComponentInterface|null $component): static;


    /**
     * Returns the element to be rendered
     *
     * @return ComponentInterface|null
     */
    public function getComponentObject(): ComponentInterface|null;


    /**
     * Render and return the HTML for this object
     *
     * @return string|null
     */
    public function render(): ?string;
}
