<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Interfaces;

use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\Interfaces\ContentObjectInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Html\Components\Span;
use Phoundation\Web\Html\Components\Widgets\Tooltips\Interfaces\TooltipInterface;
use Phoundation\Web\Http\Interfaces\UrlInterface;
use Stringable;


interface ElementAttributesInterface extends ContentObjectInterface
{
    /**
     * Returns the HTML id element attribute
     *
     * @return string|null
     */
    public function getId(): ?string;

    /**
     * Sets the HTML id element attribute
     *
     * @param string|null $id
     * @param bool        $name_too
     *
     * @return static
     */
    public function setId(?string $id, bool $name_too = true): static;

    /**
     * Returns the (optional) anchor for this element
     *
     * @return AnchorInterface
     */
    public function getAnchorObject(): AnchorInterface;

    /**
     * Sets the anchor for this element
     *
     * @param UrlInterface|AnchorInterface|null $_anchor
     *
     * @return Span
     */
    public function setAnchorObject(UrlInterface|AnchorInterface|null $_anchor): static;

    /**
     * Returns true if this element has an outer div set up
     *
     * @return bool
     */
    public function hasOuterDiv(): bool;

    /**
     * Returns the (optional) outer_element for this element
     *
     * @return DivInterface
     */
    public function getOuterDivObject(): DivInterface;

    /**
     * Sets the outer_element for this element
     *
     * @param DivInterface|null $_outer_div
     *
     * @return Span
     */
    public function setOuterDivObject(DivInterface|null $_outer_div): static;

    /**
     * Returns the tooltip title
     *
     * @return string|null
     */
    public function getTooltipTitle(): ?string;

    /**
     * Returns the tooltip title
     *
     * @param string|null $title
     *
     * @return static
     */
    public function setTooltipTitle(?string $title): static;

    /**
     * Returns the tooltip object for this element
     *
     * @return TooltipInterface
     */
    public function getTooltipObject(): TooltipInterface;

    /**
     * Adds a data-KEY(=VALUE) attribute
     *
     * @param array|string|float|int|null $value
     * @param string|int                  $key
     * @param bool                        $skip_null_values
     *
     * @return static
     */
    public function addData(array|string|float|int|null $value, string|int $key, bool $skip_null_values = true): static;

    /**
     * Returns the data attributes for the specified key
     *
     * @param string|int $key
     *
     * @return array|string|float|int|null
     */
    public function getDataKey(string|int $key): array|string|float|int|null;

    /**
     * Renders the data attributes for the specified key
     *
     * @param             $key
     * @param string|null $prefix
     *
     * @return string|null
     */
    public function renderDataKey($key, ?string $prefix = ' '): ?string;

    /**
     * Returns the HTML element data-* attribute store
     *
     * @param bool $resolve_callbacks
     *
     * @return IteratorInterface
     */
    public function getDataObject(bool $resolve_callbacks = true): IteratorInterface;

    /**
     * Sets the HTML element data-* attribute store
     *
     * @param IteratorInterface|array|null $_data
     *
     * @return static
     */
    public function setDataObject(IteratorInterface|array|null $_data): static;

    /**
     * Returns the HTML attributes as a string
     *
     * @return string|null
     */
    public function getAttributesString(): ?string;

    /**
     * Returns the HTML class element attribute store
     *
     * @return IteratorInterface
     */
    public function getAttributesObject(): IteratorInterface;

    /**
     * Sets all HTML element attributes
     *
     * @param array $_attributes
     *
     * @return static
     */
    public function setAttributesObject(array $_attributes): static;

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
     * Sets a single HTML element attributes
     *
     * @param mixed                 $value
     * @param string|float|int|null $key
     * @param bool                  $skip_null_values
     *
     * @return static
     */
    public function setAttribute(mixed $value, string|float|int|null $key = null, bool $skip_null_values = true): static;

    /**
     * Adds an aria-KEY(=VALUE) attribute
     *
     * @param string|float|int|null $value
     * @param string                $key
     *
     * @return static
     */
    public function addAria(string|float|int|null $value, string $key): static;

    /**
     * Returns the HTML element aria-* attribute store
     *
     * @param bool $resolve_callbacks
     *
     * @return IteratorInterface
     */
    public function getAriaObject(bool $resolve_callbacks = true): IteratorInterface;

    /**
     * Sets the HTML element aria-* attribute store
     *
     * @param IteratorInterface|array|null $aria
     *
     * @return static
     */
    public function setAriaObject(IteratorInterface|array|null $aria): static;

    /**
     * Returns the HTML class element attribute
     *
     * @param string|null $prefix                       If true, will prefix the class list with the specified prefix
     * @param bool        $add_definition_name_to_class If true, will add the element's name attribute to the list of
     *                                                  classes
     *
     * @return string|null
     */
    public function getClass(?string $prefix = null, bool $add_definition_name_to_class = true): ?string;

    /**
     * Adds a class to the HTML class element attribute
     *
     * @param array|string|null $classes
     *
     * @return static
     */
    public function setClass(array|string|null $classes): static;

    /**
     * Returns the HTML tabindex element attribute
     *
     * @return int|null
     */
    public function getTabIndex(): ?int;

    /**
     * Set the HTML tabindex element attribute
     *
     * @param int|null $tabindex
     *
     * @return static
     */
    public function setTabIndex(?int $tabindex): static;

    /**
     * Clears all the extra element attribute code
     *
     * @return static
     */
    public function clearExtraAttributes(): static;

    /**
     * Returns the extra element attribute code
     *
     * @return string|null
     */
    public function getExtraAttributes(): ?string;

    /**
     * Sets all the extra element attribute code
     *
     * @param string|null $extra
     *
     * @return static
     */
    public function setExtraAttributes(?string $extra): static;

    /**
     * Adds more to the extra element attribute code
     *
     * @param Stringable|string|null $extra
     *
     * @return static
     */
    public function addExtraAttributes(Stringable|string|null $extra): static;


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
     * Returns the height of the element to display
     *
     * @return int|null
     */
    public function getHeight(): ?int;

    /**
     * Sets the height of the element to display
     *
     * @param int|null $height
     *
     * @return static
     */
    public function setHeight(?int $height): static;

    /**
     * Returns the width of the element to display
     *
     * @return int|null
     */
    public function getWidth(): ?int;

    /**
     * Sets the width of the element to display
     *
     * @param int|null $width
     *
     * @return static
     */
    public function setWidth(?int $width): static;

    /**
     * Set if the button is right aligned or not
     *
     * @param bool|null $right If true, button will be right aligned, if false, button will be left aligned, if NULL, button will have default alignment
     *
     * @return static
     */
    public function setFloatRight(?bool $right): static;

    /**
     * Returns if the button is right aligned or not
     *
     * @return bool
     */
    public function getFloatRight(): bool;

    /**
     * Returns the HTML class element attribute store
     *
     * @return IteratorInterface
     */
    public function getClassesObject(): IteratorInterface;

    /**
     * Sets the HTML element class attribute
     *
     * @param IteratorInterface|array|string|null $classes
     *
     * @return static
     */
    public function setClassesObject(IteratorInterface|array|string|null $classes): static;

    /**
     * Sets the HTML class element attribute
     *
     * @param bool $auto_focus
     *
     * @return static
     */
    public function setAutofocus(bool $auto_focus): static;

    /**
     * Returns the HTML name attribute for this element
     *
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * Sets the HTML name attribute for this element
     *
     * @param string|null $name   The "name" attribute for this element
     * @param bool        $id_too If true, will make the elements id the same as the name
     *
     * @return static
     */
    public function setName(?string $name, bool $id_too = true): static;

    /**
     * Returns the HTML disabled element attribute
     *
     * @return bool
     */
    public function getDisabled(): bool;


    /**
     * Set the HTML disabled element attribute
     *
     * @param bool        $disabled
     * @param bool|null   $set_readonly
     *
     * @return static
     */
    public function setDisabled(bool $disabled, ?bool $set_readonly = null): static;

    /**
     * Returns the HTML readonly element attribute
     *
     * @return bool
     */
    public function getReadonly(): bool;


    /**
     * Set the HTML readonly element attribute
     *
     * @param bool        $readonly
     * @param bool|null   $set_disabled
     *
     * @return static
     */
    public function setReadonly(bool $readonly, ?bool $set_disabled = null): static;

    /**
     * Returns if the contents of the element should be selectable by a user, or not
     *
     * @see https://duckduckgo.com/?t=ffab&q=make+text+unselectable+in+html&atb=v446-1&ia=web&iax=qa
     *
     * @return bool
     */
    public function getSelectable(): bool;

    /**
     * Sets if the contents of the element should be selectable by a user, or not
     *
     * @see https://duckduckgo.com/?t=ffab&q=make+text+unselectable+in+html&atb=v446-1&ia=web&iax=qa
     *
     *
     * @param bool $selectable
     *
     * @return static
     */
    public function setSelectable(bool $selectable): static;

    /**
     * Returns the HTML class element attribute
     *
     * @note Returns true if the static autofocus variable was set and is equal to the ID of this specific element
     * @return bool
     */
    public function getAutofocus(): bool;

    /**
     * Returns the DataEntry Definition on this element
     *
     * If no Definition object was set, one will be created using the data in this object
     *
     * @return DefinitionInterface|null
     */
    public function getDefinitionObject(): ?DefinitionInterface;

    /**
     * Set the DataEntry Definition on this element
     *
     * @param DefinitionInterface|null $_definition
     *
     * @return static
     */
    public function setDefinitionObject(?DefinitionInterface $_definition): static;

    /**
     * Adds the specified class to the HTML element class attribute
     *
     * @param IteratorInterface|callable|array|string|null $_class
     *
     * @return static
     */
    public function addClass(IteratorInterface|callable|array|string|null $_class): static;

    /**
     * Adds the specified classes to the HTML element class attribute
     *
     * @param IteratorInterface|callable|array|string|null $_classes
     *
     * @return static
     */
    public function addClasses(IteratorInterface|callable|array|string|null $_classes): static;

    /**
     * Removes the specified classes from the HTML element class attribute
     *
     * @note This is a wrapper method for Element::removeClass()
     *
     * @param IteratorInterface|array|string|null $_class
     *
     * @return static
     */
    public function removeClass(IteratorInterface|array|string|null $_class): static;

    /**
     * Removes the specified class from the HTML element class attribute
     *
     * @param IteratorInterface|array|string|null $_classes
     *
     * @return static
     */
    public function removeClasses(IteratorInterface|array|string|null $_classes): static;

    /**
     * Renders and returns the content that come before this element
     *
     * @return string|null
     */
    public function renderBeforeContent(): ?string;

    /**
     * Renders and returns the content that come after this element
     *
     * @return string|null
     */
    public function renderAfterContent(): ?string;

    /**
     * Returns the HTML visible element attribute
     *
     * @return bool
     */
    public function getVisible(): bool;

    /**
     * Set the HTML visible element attribute
     *
     * @param bool $visible
     * @param bool $parent_only
     *
     * @return static
     */
    public function setVisible(bool $visible, bool $parent_only = true): static;

    /**
     * Returns the HTML visible element attribute
     *
     * @return bool
     */
    public function getDisplay(): bool;

    /**
     * Set the HTML visible element attribute
     *
     * @param bool $display
     * @param bool $parent_only
     *
     * @return static
     */
    public function setDisplay(bool $display, bool $parent_only = true): static;

    /**
     * Returns the HTML "required" element attribute
     *
     * @return bool
     */
    public function getRequired(): bool;

    /**
     * Set the HTML "required" element attribute
     *
     * @param bool $required
     *
     * @return static
     */
    public function setRequired(bool $required): static;

    /**
     * Returns the HTML "null_display" element attribute
     *
     * @return Stringable|string|float|int|null
     */
    public function getNullDisplay(): Stringable|string|float|int|null;


    /**
     * Set the HTML "null_display" element attribute
     *
     * @param RenderInterface|string|float|int|null $value
     * @param bool                                  $make_safe
     *
     * @return static
     */
    public function setNullDisplay(RenderInterface|string|float|int|null $value, bool $make_safe = false): static;

    /**
     * Returns if this input element has after content
     *
     * @return bool
     */
    public function hasAfterContent(): bool;

    /**
     * Returns the modal after_content
     *
     * @return array
     */
    public function getAfterContent(): array;

    /**
     * Sets the modal after_content
     *
     * @param RenderInterface|array|callable|string|null $after_content
     *
     * @return static
     */
    public function setAfterContent(RenderInterface|array|callable|string|null $after_content): static;

    /**
     * Sets the modal after_content
     *
     * @param RenderInterface|array|callable|string|null $after_content
     *
     * @return static
     */
    public function addAfterContent(RenderInterface|array|callable|string|null $after_content): static;

    /**
     * Returns if this input element has before content
     *
     * @return bool
     */
    public function hasBeforeContent(): bool;

    /**
     * Returns the modal before_content
     *
     * @return array
     */
    public function getBeforeContent(): array;

    /**
     * Sets the modal before_content
     *
     * @param RenderInterface|array|callable|string|null $before_content
     *
     * @return static
     */
    public function setBeforeContent(RenderInterface|array|callable|string|null $before_content): static;

    /**
     * Sets the modal before_content
     *
     * @param RenderInterface|array|callable|string|null $before_content
     *
     * @return static
     */
    public function addBeforeContent(RenderInterface|array|callable|string|null $before_content): static;

    /**
     * Returns the HTML title element attribute
     *
     * @return string|null
     */
    public function getTitle(): ?string;


    /**
     * Sets the HTML title element attribute
     *
     * @param string|false|null $title            The title for this object
     * @param bool              $make_safe [true] If true, will make the title safe for use with HTML
     *
     * @return static
     */
    public function setTitle(string|false|null $title, bool $make_safe = true): static;
}
