<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Interfaces;

use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Web\Html\Components\Span;
use Phoundation\Web\Html\Enums\EnumElement;
use Phoundation\Web\Http\Interfaces\UrlInterface;
use Stringable;

interface ElementInterface extends ComponentInterface, ElementAttributesInterface
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
     * @param RenderInterface|callable|string|float|int|null $content
     * @param bool                                           $make_safe
     *
     * @return static
     */
    public function appendContent(RenderInterface|callable|string|float|int|null $content, bool $make_safe = false): static;


    /**
     * Adds the specified content to the content of the element
     *
     * @param RenderInterface|callable|string|float|int|null $content
     * @param bool                                           $make_safe
     *
     * @return static
     */
    public function prependContent(RenderInterface|callable|string|float|int|null $content, bool $make_safe = false): static;


    /**
     * Returns the definition
     *
     * @return DefinitionInterface|null
     */
    public function getDefinitionObject(): ?DefinitionInterface;


    /**
     * Sets the definition
     *
     * @param DefinitionInterface|null $o_definition
     *
     * @return static
     */
    public function setDefinitionObject(DefinitionInterface|null $o_definition): static;


    /**
     * Returns the (optional) anchor for this element
     *
     * @return AnchorInterface
     */
    public function getAnchorObject(): AnchorInterface;


    /**
     * Sets the anchor for this element
     *
     * @param UrlInterface|AnchorInterface|null $o_anchor
     *
     * @return Span
     */
    public function setAnchorObject(UrlInterface|AnchorInterface|null $o_anchor): static;


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

    /**
     * Returns the HTML class element attribute
     *
     * @param string|null $prefix                       If true, will prefix the class list with the specified prefix
     * @param bool        $add_definition_name_to_class If true, will add the element's name attribute to the list of classes
     *
     * @return string|null
     */
    public function getClass(?string $prefix = null, bool $add_definition_name_to_class = true): ?string;

    /**
     * Returns the HTML name attribute for this element
     *
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * Returns if the contents of the element should be selectable by a user, or not
     *
     * @return bool
     */
    public function getSelectable(): bool;

    /**
     * Sets if the contents of the element should be selectable by a user, or not
     *
     * @param bool $selectable
     *
     * @return static
     */
    public function setSelectable(bool $selectable): static;
}
