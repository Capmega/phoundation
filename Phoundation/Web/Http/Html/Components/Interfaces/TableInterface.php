<?php

namespace Phoundation\Web\Http\Html\Components\Interfaces;


use Phoundation\Data\Interfaces\IteratorInterface;
use Stringable;

/**
 * Class Table
 *
 * This class can create various HTML tables
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
interface TableInterface
{
    /**
     * Returns if the table is header_text or not
     *
     * @return string|null
     */
    public function getHeaderText(): ?string;

    /**
     * Sets if the table is header_text or not
     *
     * @param string|null $header_text
     * @return static
     */
    public function setHeaderText(?string $header_text): static;

    /**
     * Returns if the table is responsive or not
     *
     * @return bool
     */
    public function getResponsive(): bool;

    /**
     * Sets if the table will process entities in the source data or not
     *
     * @param bool $process_entities
     * @return static
     */
    public function setProcessEntities(bool $process_entities): static;

    /**
     * Sets if the table will process entities in the source data or not
     *
     * @return bool
     */
    public function getProcessEntities(): bool;

    /**
     * Sets if the table is responsive or not
     *
     * @param bool $responsive
     * @return static
     */
    public function setResponsive(bool $responsive): static;

    /**
     * Returns if the table is full width or not
     *
     * @return bool
     */
    public function getFullWidth(): bool;

    /**
     * Sets if the table is full width or not
     *
     * @param bool $full_width
     * @return static
     */
    public function setFullWidth(bool $full_width): static;

    /**
     * Returns the table's column conversions
     *
     * @return IteratorInterface
     */
    public function getConvertColumns(): IteratorInterface;

    /**
     * Returns the table's top buttons
     *
     * @return IteratorInterface
     */
    public function getTopButtons(): IteratorInterface;

    /**
     * Returns the HTML class element attribute for <tr> tags
     *
     * @return string|null
     */
    public function getRowClasses(): ?string;

    /**
     * Sets the HTML class element attribute for <tr> tags
     *
     * @param string|null $classes
     * @return static
     */
    public function setRowClasses(?string $classes): static;

    /**
     * Returns the HTML class element attribute for <td> tags
     *
     * @return string|null
     */
    public function getColumnClasses(): ?string;

    /**
     * Sets the HTML class element attribute for <td> tags
     *
     * @param string|null $classes
     * @return static
     */
    public function setColumnClasses(?string $classes): static;

    /**
     * Returns if the first column will automatically be converted to checkboxes
     *
     * @return bool
     */
    public function getCheckboxSelectors(): bool;

    /**
     * Sets if the first column will automatically be converted to checkboxes
     *
     * @param bool $checkbox_selectors
     * @return static
     */
    public function setCheckboxSelectors(bool $checkbox_selectors): static;

    /**
     * Returns the URL that applies to each row
     *
     * @return string|null
     */
    public function getRowUrl(): ?string;

    /**
     * Sets the URL that applies to each row
     *
     * @param Stringable|string|null $row_url
     * @return static
     */
    public function setRowUrl(Stringable|string|null $row_url): static;

    /**
     * Returns the URL that applies to each column
     *
     * @return IteratorInterface
     */
    public function getColumnUrls(): IteratorInterface;

    /**
     * Returns the table headers
     *
     * @return IteratorInterface
     */
    public function getColumnHeaders(): IteratorInterface;

    /**
     * Render the table body
     *
     * @return string
     */
    public function renderBody(): string;
}