<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Layouts;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\ElementsBlock;
use Phoundation\Web\Html\Enums\Interfaces\DisplaySizeInterface;


/**
 * Grid class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Grid extends Container
{
    /**
     * Set the rows for this grid
     *
     * @param array $rows
     * @param DisplaySizeInterface|null $column_size
     * @param bool $use_form
     * @return static
     */
    public function setRows(array $rows, ?DisplaySizeInterface $column_size = null, bool $use_form = false): static
    {
        $this->source = [];
        return $this->addRows($rows, $column_size, $use_form);
    }


    /**
     * Add the specified row to this grid
     *
     * @param array $rows
     * @param DisplaySizeInterface|null $column_size
     * @param bool $use_form
     * @return static
     */
    public function addRows(array $rows, ?DisplaySizeInterface $column_size = null, bool $use_form = false): static
    {
        // Validate columns
        foreach ($rows as $row) {
            if (!is_object($row) or !($row instanceof GridRow)) {
                throw new OutOfBoundsException(tr('Invalid datatype for specified row. The row should be a GridRow object, but is a ":datatype"', [
                    ':datatype' => (is_object($row) ? get_class($row) : gettype($row))
                ]));
            }

            $this->addRow($row, $column_size, $use_form);
        }

        return $this;
    }


    /**
     * Add the specified row to this grid
     *
     * @param GridRow|GridColumn|ElementsBlock|null $row
     * @param DisplaySizeInterface|null $column_size
     * @param bool $use_form
     * @return static
     */
    public function addRow(GridRow|GridColumn|ElementsBlock|null $row = null, ?DisplaySizeInterface $column_size = null, bool $use_form = false): static
    {
        if (!$row) {
            // Just add an empty row
            $row = new GridRow();
        }

        if (!($row instanceof GridRow)) {
            // This is not a row!
            if (!($row instanceof GridColumn)) {
                // This is not even a column, it's content. Put it in a column first
                $row = GridColumn::new()->setContent($row)->useForm($use_form);
            }

            // This is a column, put the column in a row
            $row = GridRow::new()->addColumn($row, $column_size);
        }

        // We have a row
        $this->source[] = $row;

        return $this;
    }


    /**
     * Set the columns for the current row in this grid
     *
     * @param array $columns
     * @param DisplaySizeInterface|int|null $size $size
     * @param bool $use_form
     * @return static
     */
    public function setColumns(array $columns, DisplaySizeInterface|int|null $size = null, bool $use_form = false): static
    {
        $this->getCurrentRow()->clear();
        return $this->addColumns($columns, $size, $use_form);
    }


    /**
     * Add the specified column to the current row in this grid
     *
     * @param array $columns
     * @param DisplaySizeInterface|int|null $size $size
     * @param bool $use_form
     * @return static
     */
    public function addColumns(array $columns, DisplaySizeInterface|int|null $size = null, bool $use_form = false): static
    {
        foreach ($columns as $column) {
            $this->addColumn($column, $size, $use_form);
        }

        return $this;
    }


    /**
     * Add the specified column to the current row in this grid
     *
     * @param object|string|null $column
     * @param DisplaySizeInterface|int|null $size $size
     * @param bool $use_form
     * @return static
     */
    public function addColumn(object|string|null $column, DisplaySizeInterface|int|null $size = null, bool $use_form = false): static
    {
        // Get a row
        if ($this->source) {
            $row = current($this->source);
        } else {
            // Make sure we have a row
            $row = GridRow::new();
            $this->addRow($row);
        }

        if (is_object($column) and !($column instanceof GridColumn)) {
            // This is not a GridColumn object, try to render the object to HTML string
            static::canRenderHtml($column);

            if ($size === null) {
                throw new OutOfBoundsException(tr('No column size specified'));
            }

            // Render the HTML string
            $column = $column->render();
        }

        if (is_string($column)) {
            // This is not a column, it is content (should be an HTML string). Place the content in a column and add
            // that column instead
            $column = GridColumn::new()->setContent($column)->useForm($use_form);
        }

        $row->addColumn($column, $size);
        return $this;
    }

    
    /**
     * Returns the current row for this grid
     *
     * @return GridRow
     */
    protected function getCurrentRow(): GridRow
    {
        if (!$this->source) {
            $row = GridRow::new();
            $this->addRow($row);
        } else {
            $row = $this->source[array_key_last($this->source)];
        }

        return $row;
    }
}