<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components\Interfaces;


use Phoundation\Data\Iterator;
use Stringable;


/**
 * Interface ElementAttributes
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
interface ElementAttributesInterface
{
    /**
     * ElementsAttributes class constructor
     */
    function __construct();

    /**
     * Return new Templated HTML Element object using the current Page template
     *
     * return static
     */
    public static function new(): static;

    /**
     * Sets the HTML id element attribute
     *
     * @param string|null $id
     * @return static
     */
    function setId(?string $id): static;

    /**
     * Returns the HTML id element attribute
     *
     * @return string|null
     */
    function getId(): ?string;

    /**
     * Sets the HTML name element attribute
     *
     * @param string|null $name
     * @return static
     */
    function setName(?string $name): static;

    /**
     * Returns the HTML name element attribute
     *
     * @return string|null
     */
    function getName(): ?string;

    /**
     * Clears the HTML class element attribute
     *
     * @return static
     */
    function clearClasses(): static;

    /**
     * Sets the HTML class element attribute
     *
     * @param array|string|null $classes
     * @return static
     */
    function setClasses(array|string|null $classes): static;

    /**
     * Sets the HTML class element attribute
     *
     * @param array|string|null $classes
     * @return static
     */
    function addClasses(array|string|null $classes): static;

    /**
     * Adds a class to the HTML class element attribute
     *
     * @param string|null $class
     * @return static
     */
    function addClass(?string $class): static;

    /**
     * Removes the specified class for this element
     *
     * @param string $class
     * @return $this
     */
    function removeClass(string $class): static;

    /**
     * Adds a class to the HTML class element attribute
     *
     * @param ?string $class
     * @return static
     */
    function setClass(?string $class): static;

    /**
     * Returns the HTML class element attribute store
     *
     * @return array
     */
    function getClasses(): array;

    /**
     * Returns the HTML class element attribute
     *
     * @return string|null
     */
    function getClass(): ?string;

    /**
     * Returns if this element has the specified class or not
     *
     * @param string $class
     * @return bool
     */
    function hasClass(string $class): bool;

    /**
     * Returns the HTML element data attribute store
     *
     * @return Iterator
     */
    function getData(): Iterator;

    /**
     * Returns the HTML element aria attribute store
     *
     * @return Iterator
     */
    function getAria(): Iterator;

    /**
     * Set the HTML tabindex element attribute
     *
     * @param int|null $tabindex
     * @return static
     */
    function setTabIndex(?int $tabindex): static;

    /**
     * Returns the HTML tabindex element attribute
     *
     * @return int|null
     */
    function getTabIndex(): ?int;

    /**
     * Clears all the extra element attribute code
     *
     * @return static
     */
    function clearExtra(): static;

    /**
     * Sets all the extra element attribute code
     *
     * @param string|null $extra
     * @return static
     */
    function setExtra(?string $extra): static;

    /**
     * Adds more to the extra element attribute code
     *
     * @param string|null $extra
     * @return static
     */
    function addExtra(?string $extra): static;

    /**
     * Returns the extra element attribute code
     *
     * @return string
     */
    function getExtra(): string;

    /**
     * Sets the HTML class element attribute
     *
     * @param bool $auto_focus
     * @return static
     */
    public function setAutofocus(bool $auto_focus): static;

    /**
     * Returns the HTML class element attribute
     *
     * @return bool
     */
    function getAutofocus(): bool;

    /**
     * Set the HTML disabled element attribute
     *
     * @param bool $disabled
     * @return static
     */
    function setDisabled(bool $disabled): static;

    /**
     * Returns the HTML disabled element attribute
     *
     * @return bool
     */
    function getDisabled(): bool;

    /**
     * Set the HTML readonly element attribute
     *
     * @param bool $readonly
     * @return static
     */
    function setReadonly(bool $readonly): static;

    /**
     * Returns the HTML readonly element attribute
     *
     * @return bool
     */
    function getReadonly(): bool;

    /**
     * Clears all HTML element attributes
     *
     * @return static
     */
    function clearAttributes(): static;

    /**
     * Sets all HTML element attributes
     *
     * @param array $notifications
     * @return static
     */
    function setAttributes(array $notifications): static;

    /**
     * Sets all HTML element attributes
     *
     * @param array $attributes
     * @return static
     */
    function addAttributes(array $attributes): static;

    /**
     * Sets all HTML element attributes
     *
     * @param string $attribute
     * @param string|null $value
     * @param bool $skip_on_null
     * @return static
     */
    function addAttribute(string $attribute, ?string $value, bool $skip_on_null = false): static;

    /**
     * Returns all HTML element attributes
     *
     * @return array
     */
    function getAttributes(): array;

    /**
     * Sets the content of the element
     *
     * @param Stringable|string|float|int|null $content
     * @param bool $make_safe
     * @return static
     */
    function setContent(Stringable|string|float|int|null $content, bool $make_safe = false): static;

    /**
     * Adds the specified content to the content of the element
     *
     * @param Stringable|string|float|int|null $content
     * @return static
     */
    function addContent(Stringable|string|float|int|null $content): static;

    /**
     * Returns the content of the element to display
     *
     * @return string|null
     */
    function getContent(): ?string;

    /**
     * Sets the height of the element to display
     *
     * @param int|null $height
     * @return static
     */
    function setHeight(?int $height): static;

    /**
     * Returns the height of the element to display
     *
     * @return int|null
     */
    function getHeight(): ?int;

    /**
     * Sets the width of the element to display
     *
     * @param int|null $width
     * @return static
     */
    function setWidth(?int $width): static;

    /**
     * Returns the width of the element to display
     *
     * @return int|null
     */
    function getWidth(): ?int;

    /**
     * Set if the button is right aligned or not
     *
     * @param bool $right
     * @return static
     */
    function setRight(bool $right): static;

    /**
     * Returns if the button is right aligned or not
     *
     * @return string
     */
    function getRight(): string;

    /**
     * Ensures that the specified object has ElementAttributes
     *
     * @note This is just a wrapper around ElementAttributes::ensureElementAttributesTrait(). While that function
     *       explains more clearly what it does, this one says more clearly WHY and as such is the public one.
     * @param object|string $class
     * @return void
     * @see ElementAttributes::ensureElementAttributesTrait()
     */
    static function canRenderHtml(object|string $class): void;
 }