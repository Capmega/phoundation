<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Interfaces;

use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Html\Components\Forms\Interfaces\FormInterface;
use Phoundation\Web\Html\Html;
use Phoundation\Web\Http\Interfaces\UrlInterface;
use Stringable;

interface ContentInterface
{
    /**
     * Appends the specified content to the content of the element
     *
     * @param RenderInterface|callable|string|float|int|null $content
     * @param bool                                           $make_safe
     *
     * @return static
     */
    public function appendContent(RenderInterface|callable|string|float|int|null $content, bool $make_safe = false): static;

    /**
     * Prepends the specified content to the content of the element
     *
     * @param RenderInterface|callable|string|float|int|null $content
     * @param bool                                           $make_safe
     *
     * @return static
     */
    public function prependContent(RenderInterface|callable|string|float|int|null $content, bool $make_safe = false): static;

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


    /**
     * Returns if this object will be rendered when the content is empty
     *
     * @return bool
     */
    public function getRenderOnEmptyContent(): bool;

    /**
     *  Sets if this object will be rendered when the content is empty
     *
     * @param bool $render_on_empty_content
     *
     * @return static
     */
    public function setRenderOnEmptyContent(bool $render_on_empty_content = true): static;
}
