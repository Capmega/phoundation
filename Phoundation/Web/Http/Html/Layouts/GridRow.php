<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Layouts;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Interfaces\InterfaceDisplaySize;


/**
 * GridRow class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class GridRow extends Layout
{
    /**
     * Clear the source in this row
     *
     * @return static
     */
    public function clearColumns(): static
    {
        $this->source = [];
        return $this;
    }


    /**
     * Returns the source for this row
     *
     * @return array
     */
    public function getColumns(): array
    {
        return $this->source;
    }


    /**
     * Set the source for this row
     *
     * @param array $source
     * @param InterfaceDisplaySize|int|null $size $size
     * @return static
     */
    public function setColumns(array $source, InterfaceDisplaySize|int|null $size = null): static
    {
        $this->source = [];
        return $this->addColumns($source, $size);
    }


    /**
     * Add the specified source to this row
     *
     * @param array $source
     * @param InterfaceDisplaySize|int|null $size
     * @return static
     */
    public function addColumns(array $source, InterfaceDisplaySize|int|null $size = null): static
    {
        // Validate source
        foreach ($source as $column) {
            if (!is_object($column) and !is_string($column)) {
                throw new OutOfBoundsException(tr('Invalid datatype for specified column. The column should be an object or a string, but is a ":datatype"', [
                    ':datatype' => gettype($column)
                ]));
            }

            $this->addColumn($column, $size);
        }

        return $this;
    }


    /**
     * Add the specified column to this row
     *
     * @param object|string|null $column
     * @param InterfaceDisplaySize|int|null $size $size
     * @return static
     */
    public function addColumn(object|string|null $column, InterfaceDisplaySize|int|null $size = null): static
    {
        if ($column) {
            if (is_object($column) and !($column instanceof GridColumn)) {
                // This is not a GridColumn object, try to render the object to HTML string
                static::canRenderHtml($column);

                // Render the HTML string
                $column = $column->render();
            }

            if (is_string($column)) {
                // This is not a column, it is content (should be an HTML string). Place the content in a column and add
                // that column instead
                $column = GridColumn::new()->setContent($column);
            }

            // Shortcut to set column size
            if ($size !== null) {
                $column->setSize($size);
            }

            $this->source[] = $column;
        }

        return $this;
    }
}