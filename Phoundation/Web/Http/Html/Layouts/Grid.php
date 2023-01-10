<?php

namespace Phoundation\Web\Http\Html\Layouts;

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
     * @param object|string|null $column
     * @return static
     */
    public function addColumn(object|string|null $column): static
    {
        // Get a row
        if (isset($this->rows)) {
            $row = current($this->rows);
        } else {
            // Make sure we have a row
            $row = GridRow::new();
            $this->addRow($row);
        }

        if (is_object($column) and !($column instanceof GridColumn)) {
            // This is not a GridColumn object, try to render the object to HTML string
            if (!method_exists($column, 'render')) {
                throw new OutOfBoundsException(tr('Specified ":class" class object does not have ::render() method. Please specify either a GridColumn class object, or an HTML string or an object that has a ::render() method.', [
                    ':class' => get_class($column)
                ]));
            }

            // Render the HTML string
            $column = $column->render();
        }

        if (is_string($column)) {
            // This is not a column, it is content (should be an HTML string). Place the content in a column and add
            // that column instead
            $column = GridColumn::new()->setContent($column);
        }

        $row->addColumn($column);
        return $this;
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
            $row = $this->rows[array_key_last($this->rows)];
        }

        return $row;
    }
}