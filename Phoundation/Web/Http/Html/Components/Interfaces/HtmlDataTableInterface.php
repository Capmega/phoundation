<?php

namespace Phoundation\Web\Http\Html\Components\Interfaces;

use Phoundation\Web\Http\Html\Enums\Interfaces\PagingTypeInterface;


/**
 * Interface HtmlDataTableInterface
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
interface HtmlDataTableInterface extends HtmlTableInterface
{
    /**
     * Returns table top-buttons
     *
     * @return array
     */
    public function getButtons(): array;

    /**
     * Sets table top-buttons
     *
     * @param array|string|null $buttons
     * @return $this
     */
    public function setButtons(array|string|null $buttons): static;

    /**
     * Returns if responsive table is enabled or not
     *
     * @return bool|null
     */
    public function getResponsiveEnabled(): ?bool;

    /**
     * Sets if responsive table is enabled or not
     *
     * @param bool|null $enabled
     * @return $this
     */
    public function setResponsiveEnabled(?bool $enabled): static;

    /**
     * Returns responsive breakpoints
     *
     * @return array|null
     */
    public function getResponsiveBreakpoints(): ?array;

    /**
     * Sets responsive breakpoints
     *
     * @param array|null $breakpoints
     * @return $this
     */
    public function setResponsiveBreakpoints(?array $breakpoints): static;

    /**
     * Returns if table information display field is enabled or not
     *
     * @return bool|null
     */
    public function getInfoEnabled(): ?bool;

    /**
     * Sets if table information display field is enabled or not
     *
     * @param bool|null $enabled
     * @return $this
     */
    public function setInfoEnabled(?bool $enabled): static;

    /**
     * Returns if search (filtering) abilities are enabled or disabled
     *
     * @return bool|null
     */
    public function getSearchingEnabled(): ?bool;

    /**
     * Sets if search (filtering) abilities are enabled or disabled
     *
     * @param bool|null $enabled
     * @return $this
     */
    public function setSearchingEnabled(?bool $enabled): static;

    /**
     * Returns if case-sensitive filtering is enabled or not
     *
     * @return bool|null
     */
    public function getSearchCaseInsensitiveEnabled(): ?bool;

    /**
     * Returns if escaping of regular expression characters in the search term is enabled or not
     *
     * @param bool|null $enabled
     * @return $this
     */
    public function setSearchCaseInsensitiveEnabled(?bool $enabled): static;

    /**
     * Returns if escaping of regular expression characters in the search term is enabled or not
     *
     * @return bool|null
     */
    public function getSearchRegexEnabled(): ?bool;

    /**
     * Sets if escaping of regular expression characters in the search term is enabled or not
     *
     * @param bool|null $enabled
     * @return $this
     */
    public function setSearchRegexEnabled(?bool $enabled): static;

    /**
     * Returns if DataTables' smart filtering is enabled or not
     *
     * @return bool|null
     */
    public function getSearchSmartEnabled(): ?bool;

    /**
     * Sets if DataTables' smart filtering is enabled or not
     *
     * @param bool|null $enabled
     * @return $this
     */
    public function setSearchSmartEnabled(?bool $enabled): static;

    /**
     * Returns if search on return is enabled or not
     *
     * @return bool|null
     */
    public function getSearchReturnEnabled(): ?bool;

    /**
     * Sets if search on return is enabled or not
     *
     * @param bool|null $enabled
     * @return $this
     */
    public function setSearchReturnEnabled(?bool $enabled): static;

    /**
     * Returns the initial filtering condition on the table
     *
     * @return string
     */
    public function getSearch(): string;

    /**
     * Sets the initial filtering condition on the table
     *
     * @param string|null $search
     * @return $this
     */
    public function setSearch(?string $search): static;

    /**
     * Returns if paging is enabled or disabled
     *
     * @return bool|null
     */
    public function getPagingEnabled(): ?bool;

    /**
     * Sets if paging is enabled or disabled
     *
     * @param bool|null $enabled
     * @return $this
     */
    public function setPagingEnabled(?bool $enabled): static;

    /**
     * Sets pagination button display options
     *
     * @return PagingTypeInterface
     */
    public function getPagingType(): PagingTypeInterface;

    /**
     * Sets pagination button display options
     *
     * @param PagingTypeInterface $type
     * @return $this
     */
    public function setPagingType(PagingTypeInterface $type): static;

    /**
     * Returns the menu available to the user displaying the optional paging lengths
     *
     * @return array
     */
    public function getLengthMenu(): array;

    /**
     * Sets the menu available to the user displaying the optional paging lengths
     *
     * @param array|null $length_menu
     * @return $this
     */
    public function setLengthMenu(?array $length_menu): static;

    /**
     * Returns if the length menu is displayed or not
     *
     * @return bool|null
     */
    public function getLengthChangeEnabled(): ?bool;

    /**
     * Sets if the length menu is displayed or not
     *
     * @param bool|null $enabled
     * @return $this
     */
    public function setLengthChangeEnabled(?bool $enabled): static;

    /**
     * Returns the default page length
     *
     * @return int
     */
    public function getPageLength(): int;

    /**
     * Sets the default page length
     *
     * @param int $length
     * @return $this
     */
    public function setPageLength(int $length): static;

    /**
     * Sets the feature control DataTables' smart column width handling
     *
     * @return bool|null
     */
    public function getAutoWidthEnabled(): ?bool;

    /**
     * Sets the feature control DataTables' smart column width handling
     *
     * @param bool|null $enabled
     * @return $this
     */
    public function setAutoWidthEnabled(?bool $enabled): static;

    /**
     * Returns if deferred rendering for additional speed of initialisation is used
     *
     * @return bool|null
     */
    public function getDeferRenderEnabled(): ?bool;

    /**
     * Sets if deferred rendering for additional speed of initialisation is used
     *
     * @param bool|null $enabled
     * @return $this
     */
    public function setDeferRenderEnabled(?bool $enabled): static;

    /**
     * Returns initial paging start point.
     *
     * @return int
     */
    public function getDisplayStart(): int;

    /**
     * Sets initial paging start point.
     *
     * @param int $start
     * @return $this
     */
    public function setDisplayStart(int $start): static;

    /**
     * Returns initial order (sort) to apply to the table
     *
     * @return array
     */
    public function getOrder(): array;

    /**
     * Sets initial order (sort) to apply to the table
     *
     * @param array|null $order
     * @return $this
     */
    public function setOrder(?array $order): static;

    /**
     * Returns ordering to always be applied to the table
     *
     * @return array
     */
    public function getOrderFixed(): array;

    /**
     * Sets ordering to always be applied to the table
     *
     * @param array|null $order
     * @return $this
     */
    public function setOrderFixed(?array $order): static;

    /**
     * Returns the columns that can be ordered
     *
     * @return array
     */
    public function getColumnsOrderable(): array;

    /**
     * Sets the columns that can be ordered
     *
     * @param array|null $columns
     * @return $this
     */
    public function setColumnsOrderable(?array $columns): static;

    /**
     * Sets if ordering (sorting) abilities are available in DataTables
     *
     * @return bool|null
     */
    public function getOrderingEnabled(): ?bool;

    /**
     * Sets if ordering (sorting) abilities are available in DataTables
     *
     * @param bool|null $enabled
     * @return $this
     */
    public function setOrderingEnabled(?bool $enabled): static;

    /**
     * Sets if the columns being ordered in the table's body is highlighted
     *
     * @return bool|null
     */
    public function getOrderClassesEnabled(): ?bool;

    /**
     * Sets if the columns being ordered in the table's body is highlighted
     *
     * @param bool|null $enabled
     * @return $this
     */
    public function setOrderClassesEnabled(?bool $enabled): static;

    /**
     * Returns if multiple column ordering ability is available or not
     *
     * @return bool|null
     */
    public function getOrderMultiEnabled(): ?bool;

    /**
     * Sets if multiple column ordering ability is available or not
     *
     * @param bool|null $enabled
     * @return $this
     */
    public function setOrderMultiEnabled(?bool $enabled): static;

    /**
     * Generates and returns the HTML string for this resource element
     *
     * @return string|null
     */
    public function render(): ?string;
}
