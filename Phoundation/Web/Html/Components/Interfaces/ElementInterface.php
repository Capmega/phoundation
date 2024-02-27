<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Interfaces;

use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Web\Html\Components\A;
use Phoundation\Web\Html\Components\Input\Interfaces\RenderInterface;
use Phoundation\Web\Html\Components\Span;
use Stringable;


/**
 * Class Element
 *
 * This class is an abstract HTML element object class
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
interface ElementInterface extends RenderInterface
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
     * Adds the specified content to the content of the element
     *
     * @param Stringable|string|float|int|null $content
     * @param bool $make_safe
     * @return static
     */
    public function appendContent(Stringable|string|float|int|null $content, bool $make_safe = false): static;

    /**
     * Adds the specified content to the content of the element
     *
     * @param Stringable|string|float|int|null $content
     * @param bool $make_safe
     * @return static
     */
    public function prependContent(Stringable|string|float|int|null $content, bool $make_safe = false): static;

    /**
     * Returns the definition
     *
     * @return DefinitionInterface|null
     */
    public function getDefinition(): ?DefinitionInterface;

    /**
     * Sets the definition
     *
     * @param DefinitionInterface|null $definition
     * @return static
     */
    public function setDefinition(DefinitionInterface|null $definition): static;

    /**
     * Returns the (optional) anchor for this element
     *
     * @return AInterface
     */
    public function getAnchor(): AInterface;

    /**
     * Sets the anchor for this element
     *
     * @param AInterface|null $anchor
     * @return Span
     */
    public function setAnchor(?AInterface $anchor): static;
}
