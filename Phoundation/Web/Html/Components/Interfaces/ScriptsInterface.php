<?php

namespace Phoundation\Web\Html\Components\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Html\Components\Input\Interfaces\RenderInterface;


interface ScriptsInterface extends IteratorInterface, RenderInterface
{
    /**
     * Renders and returns all Script classes in this Iterator
     *
     * @note Since Script class renders may be attached to page headers or footers (in which case that Script class
     *       would return NULL for rendering) this method may return NULL even if it rendered multiple Script classes.
     *
     * @return string|null
     */
    public function render(): ?string;

    /**
     * Returns true if the object has been rendered (and Object::render() will return cached render data), false
     * otherwise
     *
     * @return bool
     */
    public function hasRendered(): bool;


    /**
     * Clears the render cache for this object
     *
     * @return static
     */
    public function clearRenderCache(): static;
}
