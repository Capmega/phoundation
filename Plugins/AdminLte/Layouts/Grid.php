<?php

namespace Plugins\Mdb\Layouts;

use Phoundation\Exception\OutOfBoundsException;



/**
 * MDB Plugin Grid class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\Mdb
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
     * Render the HTML for this grid
     *
     * @return string
     */
    public function render(): string
    {
        $this->content = '';

        foreach ($this->rows as $row) {
            $this->content .= $row->render();
        }

        return parent::render();
    }
}