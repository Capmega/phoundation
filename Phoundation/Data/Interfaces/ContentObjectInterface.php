<?php

declare(strict_types=1);

namespace Phoundation\Data\Interfaces;

use Phoundation\Web\Html\Components\Interfaces\RenderInterface;


interface ContentObjectInterface
{
    /**
     * Returns the content of the element to display
     *
     * @return RenderInterface|string|float|int|null
     */
    public function getContent(): RenderInterface|string|float|int|null;


    /**
     * Sets the content of the element
     *
     * @param RenderInterface|callable|string|float|int|null $content
     * @param bool                                           $make_safe
     *
     * @return static
     */
    public function setContent(RenderInterface|callable|string|float|int|null $content, bool $make_safe = false): static;
}
