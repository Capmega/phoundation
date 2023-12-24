<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Interfaces;

use PDOStatement;
use Phoundation\Data\Interfaces\IteratorInterface;


/**
 * Interface ResourceElementInterface
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
    public function setObjectEmpty(?string $empty): static;

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
     * Returns the array source
     *
     * @return IteratorInterface|null
     */
    public function getSource(): ?IteratorInterface;

    /**
     * Sets the array source
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @param array|string|null $execute
     * @return static
     */
    public function setSource(IteratorInterface|PDOStatement|array|string|null $source, array|string|null $execute = null): static;

    /**
     * Returns the array source
     *
     * @return PDOStatement|null
     */
    public function getSourceQuery(): ?PDOStatement;

    /**
     * Sets a query source
     *
     * @param PDOStatement|string|null $source_query
     * @param array|string|null $execute
     * @return $this
     */
    public function setSourceQuery(PDOStatement|string|null $source_query, array|string|null $execute = null): static;

    /**
     * Sets the source for "data-*" attributes where the data key matches the source key
     *
     * @note The format should be as follows: [id => [key => value, key => value], id => [...] ...] This format will
     *       then add the specified keys to each option where the value matches the id
     * @param array $source_data
     * @return static
     */
    public function setSourceData(array $source_data): static;

    /**
     * Returns the source for "data-*" attributes where the data key matches the source key
     *
     * @note The format should be as follows: [id => [key => value, key => value], id => [...] ...] This format will
     *       then add the specified keys to each option where the value matches the id
     * @return array
     */
    public function getSourceData(): array;

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
