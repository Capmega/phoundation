<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Interfaces;

use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Web\Html\Components\Input\Interfaces\RenderInterface;
use Phoundation\Web\Html\Components\Span;
use Phoundation\Web\Html\Enums\EnumElement;
use Stringable;

interface ElementInterface extends RenderInterface
{
    /**
     * Sets the type of element to display
     *
     * @param EnumElement|string|null $element
     *
     * @return static
     */
    public function setElement(EnumElement|string|null $element): static;


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
     * @param bool                             $make_safe
     *
     * @return static
     */
    public function appendContent(Stringable|string|float|int|null $content, bool $make_safe = false): static;


    /**
     * Adds the specified content to the content of the element
     *
     * @param Stringable|string|float|int|null $content
     * @param bool                             $make_safe
     *
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
     *
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
     *
     * @return Span
     */
    public function setAnchor(?AInterface $anchor): static;


    /**
     * Returns the HTML attributes as a string
     *
     * @return string|null
     */
    public function getAttributesString(): ?string;


    /**
     * Adds the specified attribute
     *
     * @param string|float|int|null $value
     * @param string                $key
     *
     * @return static
     */
    public function addAttribute(string|float|int|null $value, string $key): static;
}
