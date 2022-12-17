<?php

namespace Templates\AdminLte\Layouts;

use Phoundation\Core\Log;
use Phoundation\Exception\OutOfBoundsException;



/**
 * AdminLte Plugin GridRow class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class GridRow extends Layout
{
    /**
     * The columns for this row
     *
     * @var array $columns
     */
    protected array $columns;



    /**
     * Returns a new GridRow object
     *
     * @return static
     */
    public static function new(): static
    {
        return new static();
    }



    /**
     * Set the columns for this row
     *
     * @param array $columns
     * @return static
     */
    public function setColumns(array $columns): static
    {
        $this->columns = [];
        return $this->addColumns($columns);
    }



    /**
     * Add the specified columns to this row
     *
     * @param array $columns
     * @return static
     */
    public function addColumns(array $columns): static
    {
        // Validate columns
        foreach ($columns as $column) {
            if (!is_object($column) or !($column instanceof GridColumn)) {
                throw new OutOfBoundsException(tr('Invalid datatype for specified column. The column should be a GridColumn object, but is a ":datatype"', [
                    ':datatype' => (is_object($column) ? get_class($column) : gettype($column))
                ]));
            }

            $this->addColumn($column);
        }

        return $this;
    }



    /**
     * Add the specified column to this row
     *
     * @param GridColumn|null $column
     * @return static
     */
    public function addColumn(?GridColumn $column): static
    {
        if ($column) {
            $this->columns[] = $column;
        }

        return $this;
    }



    /**
     * Render this grid row
     *
     * @return string
     */
    public function render(): string
    {
        $return = '<div class="row">';

        foreach ($this->columns as $column) {
            $return .= $column->render();
        }

        return $return . '</div>';
    }
}