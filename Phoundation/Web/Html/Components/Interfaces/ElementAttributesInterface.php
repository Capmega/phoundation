<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Html\Components\TraitElementAttributes;
use Stringable;

interface ElementAttributesInterface
{
    /**
     * ElementsAttributes class constructor
     */
    function __construct();


    /**
     * Return new Templated HTML Element object using the current Page template
     *
     * @return static
     */
    public static function new(): static;


    /**
     * Sets the HTML id element attribute
     *
     * @param string|null $id
     *
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
     *
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
     * Returns all HTML element attributes
     *
     * @return IteratorInterface
     */
    function getAttributes(): IteratorInterface;


    /**
     * Returns the HTML element data attribute store
     *
     * @return IteratorInterface
     */
    function getData(): IteratorInterface;


    /**
     * Returns the HTML element aria attribute store
     *
     * @return IteratorInterface
     */
    function getAria(): IteratorInterface;


    /**
     * Returns the HTML element class attribute store
     *
     * @return IteratorInterface
     */
    function getClasses(): IteratorInterface;


    /**
     * Returns the HTML class element attribute
     *
     * @param string|null $prefix
     *
     * @return string|null
     */
    function getClass(?string $prefix = null): ?string;


    /**
     * Set the HTML tabindex element attribute
     *
     * @param int|null $tabindex
     *
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
     * Sets the HTML class element attribute
     *
     * @param bool $auto_focus
     *
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
     *
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
     *
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
     * Sets the content of the element
     *
     * @param Stringable|string|float|int|null $content
     * @param bool                             $make_safe
     *
     * @return static
     */
    function setContent(Stringable|string|float|int|null $content, bool $make_safe = false): static;


    /**
     * Adds the specified content to the content of the element
     *
     * @param Stringable|string|float|int|null $content
     *
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
     *
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
     *
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
     *
     * @return static
     */
    function setRight(bool $right): static;


    /**
     * Returns if the button is right aligned or not
     *
     * @return string
     */
    function getRight(): string;
}
