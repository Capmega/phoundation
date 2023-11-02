<?php

namespace Phoundation\Web\Http\Html\Components\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Http\Html\Enums\Interfaces\TableIdColumnInterface;
use Stringable;


/**
 * Class HtmlTable
 *
 * This class can create various HTML tables
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
interface HtmlTableInterface extends ResourceElementInterface
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
     * Returns the column's data attributes
     *
     * @return IteratorInterface
     */
    public function getColumnDataAttributes(): IteratorInterface;

    /**
     * Returns the column's data attributes
     *
     * @return IteratorInterface
     */
    public function getAnchorDataAttributes(): IteratorInterface;

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
     * Returns the classes used for <tr> tags
     *
     * @return string|null
     */
    public function getRowClasses(): ?string;

    /**
     * Returns the HTML class element attribute
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
     * Returns the HTML class element attribute for <td> tags
     *
     * @return string|null
     */
    public function getAnchorClasses(): ?string;

    /**
     * Sets the HTML class element attribute for <td> tags
     *
     * @param string|null $classes
     * @return static
     */
    public function setAnchorClasses(?string $classes): static;

    /**
     * Returns if the first column will automatically be converted to checkboxes
     *
     * @return TableIdColumnInterface
     */
    public function getTableIdColumn(): TableIdColumnInterface;

    /**
     * Sets if the first column will automatically be converted to checkboxes
     *
     * @param TableIdColumnInterface $checkbox_selectors
     * @return static
     */
    public function setTableIdColumn(TableIdColumnInterface $checkbox_selectors): static;

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
    public function getHeaders(): IteratorInterface;

    /**
     * Returns the table headers
     *
     * @return IteratorInterface
     */
    public function getFooters(): IteratorInterface;

    /**
     * Returns the table headers
     *
     * @param IteratorInterface|array|null $footers
     * @return static
     */
    public function setFooters(IteratorInterface|array|null $footers): static;

    /**
     * Render the table body
     *
     * @return string
     */
    public function renderBody(): string;
}
