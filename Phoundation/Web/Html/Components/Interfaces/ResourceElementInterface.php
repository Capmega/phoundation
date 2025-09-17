<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Interfaces;

use PDOStatement;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Html\Components\ResourceElementCore;

interface ResourceElementInterface extends ElementInterface
{
    /**
     * Set the HTML none element attribute
     *
     * @param string|null $label
     *
     * @return static
     */
    public function setNotSelectedLabel(?string $label): static;


    /**
     * Returns the HTML none element attribute
     *
     * @return string|null
     */
    public function getNotSelectedLabel(): ?string;


    /**
     * Returns the HTML empty element attribute
     *
     * @return string|null
     */
    public function getComponentEmptyLabel(): ?string;


    /**
     * Sets the HTML empty element attribute
     *
     * @param string|null $label
     *
     * @return static
     */
    public function setComponentEmptyLabel(?string $label): static;


    /**
     * Returns whether query sources will be cached or not
     *
     * @return bool
     */
    public function getUseCache(): bool;


    /**
     * Sets whether query sources will be cached or not
     *
     * @param bool $cache
     *
     * @return static
     */
    public function setUseCache(bool $cache): static;


    /**
     * Sets if this element will be hidden (Element::render() will return an empty string) if the resource is empty
     *
     * @param bool $hide_empty
     *
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
     * Returns the source array for this object
     *
     * @return array
     */
    public function getSource(): array;


    /**
     * Sets the source array for this object
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null                                       $execute
     *
     * @return static
     */
    public function setSource(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null): static;


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
     * @param array|string|null        $execute
     *
     * @return static
     */
    public function setSourceQuery(PDOStatement|string|null $source_query, array|string|null $execute = null): static;


    /**
     * Sets the source for "data-*" attributes where the data key matches the source key
     *
     * @note The format should be as follows: [id => [key => value, key => value], id => [...] ...] This format will
     *       then add the specified keys to each option where the value matches the id
     *
     * @param array $data_source
     *
     * @return static
     */
    public function setDataAttributesSource(array $data_source): static;


    /**
     * Returns the source for "data-*" attributes where the data key matches the source key
     *
     * @note The format should be as follows: [id => [key => value, key => value], id => [...] ...] This format will
     *       then add the specified keys to each option where the value matches the id
     * @return array
     */
    public function getDataAttributesSource(): array;


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

    /**
     * Returns the source for "data-*" attributes where the data key matches the source key
     *
     * @param string|float|int|null $value
     * @param string                $key
     * @param int                   $row_id
     *
     * @return ResourceElementCore
     */
    public function addToDataAttributes(string|float|int|null $value, string $key, int $row_id): static;
}
