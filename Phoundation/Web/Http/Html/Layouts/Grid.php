<?php

namespace Phoundation\Web\Http\Html\Layouts;

use mysql_xdevapi\RowResult;
use Phoundation\Exception\OutOfBoundsException;



/**
 * Grid class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Grid extends Container
{
    /**
     * The rows for this grid
     *
     * @var array $rows
     */
    protected array $rows;



    /**
     * Set the rows for this grid
     *
     * @param array $rows
     * @return static
     */
    public function setRows(array $rows): static
    {
        $this->rows = [];
        return $this->addRows($rows);
    }



    /**
     * Add the specified row to this grid
     *
     * @param array $rows
     * @return static
     */
    public function addRows(array $rows): static
    {
        // Validate columns
        foreach ($rows as $row) {
            if (!is_object($row) or !($row instanceof GridRow)) {
                throw new OutOfBoundsException(tr('Invalid datatype for specified row. The row should be a GridRow object, but is a ":datatype"', [
                    ':datatype' => (is_object($row) ? get_class($row) : gettype($row))
                ]));
            }

            $this->addRow($row);
        }

        return $this;
    }



    /**
     * Add the specified row to this grid
     *
     * @param GridRow|null $row
     * @return static
     */
    public function addRow(?GridRow $row): static
    {
        if ($row) {
            $this->rows[] = $row;
        }

        return $this;
    }



    /**
     * Returns the rows for this grid
     *
     * @return array
     */
    public function getRows(): array
    {
        return $this->rows;
    }



    /**
     * Set the columns for the current row in this grid
     *
     * @param array $columns
     * @return static
     */
    public function setColumns(array $columns): static
    {
        $this->getCurrentRow()->setColumns($columns);
        return $this;
    }



    /**
     * Add the specified column to the current row in this grid
     *
     * @param array $columns
     * @return static
     */
    public function addColumns(array $columns): static
    {
        $this->getCurrentRow()->addColumns($columns);
        return $this;
    }



    /**
     * Add the specified column to the current row in this grid
     *
     * @param GridColumn|null $column
     * @return static
     */
    public function addColumn(?GridColumn $column): static
    {
        $this->getCurrentRow()->addColumn($column);
        return $this;
    }


    
    /**
     * Render the HTML for this grid
     *
     * @return string|null
     */
    public function render(): ?string
    {
        return '';
    }



    /**
     * Returns the current row for this grid
     *
     * @return GridRow
     */
    protected function getCurrentRow(): GridRow
    {
        if (!$this->rows) {
            $row = GridRow::new();
            $this->addRow($row);
        } else {
            $row = $this->rows(array_key_last($this->rows));
        }

        return $row;
    }
}