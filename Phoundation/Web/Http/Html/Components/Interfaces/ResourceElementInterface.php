<?php

namespace Phoundation\Web\Http\Html\Components\Interfaces;

use PDOStatement;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Http\Html\Components\ResourceElement;


/**
 * Class RenderElement
 *
 * This class is an abstract HTML element object class that can display resource data
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
interface ResourceElementInterface extends ElementInterface
{
    /**
     * Set the HTML none element attribute
     *
     * @param string|null $none
     * @return static
     */
    public function setNone(?string $none): static;

    /**
     * Returns the HTML none element attribute
     *
     * @return string|null
     */
    public function getNone(): ?string;

    /**
     * Returns the HTML empty element attribute
     *
     * @return string|null
     */
    public function getEmpty(): ?string;

    /**
     * Sets the HTML empty element attribute
     *
     * @param string|null $empty
     * @return static
     */
    public function setEmpty(?string $empty): static;

    /**
     * Returns whether query sources will be cached or not
     *
     * @return bool
     */
    public function getCache(): bool;

    /**
     * Sets whether query sources will be cached or not
     *
     * @param bool $cache
     * @return static
     */
    public function setCache(bool $cache): static;

    /**
     * Sets if this element will be hidden (Element::render() will return an empty string) if the resource is empty
     *
     * @param bool $hide_empty
     * @return static
     */
    public function setHideEmpty(bool $hide_empty): static;

    /**
     * Returns if this element will be hidden (Element::render() will return an empty string) if the resource is empty
     *
     * @return bool
     */
    public function getHideEmpty(): bool;

    /**
     * Returns the resource element source
     *
     * @return IteratorInterface|null
     */
    public function getSource(): ?IteratorInterface;

    /**
     * Sets the resource element source
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @param array|string|null $execute
     * @return static
     */
    public function setSource(IteratorInterface|PDOStatement|array|string|null $source, array|string|null $execute = null): static;

    /**
     * Generates and returns the HTML string for this resource element
     *
     * @return string|null
     */
    public function render(): ?string;

    /**
     * Generates and returns the HTML body
     *
     * @return string|null
     */
    public function renderBody(): ?string;
}