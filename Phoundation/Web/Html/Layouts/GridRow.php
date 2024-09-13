<?php

/**
 * GridRow class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Layouts;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Enums\EnumDisplaySize;


class GridRow extends Layout
{
    /**
     * Set the source for this row
     *
     * @param array                    $source
     * @param EnumDisplaySize|int|null $size
     * @param bool                     $use_form
     *
     * @return static
     */
    public function setGridColumns(array $source, EnumDisplaySize|int|null $size = null, bool $use_form = false): static
    {
        $this->source = [];

        return $this->addGridColumns($source, $size, $use_form);
    }


    /**
     * Add the specified source to this row
     *
     * @param array                    $source
     * @param EnumDisplaySize|int|null $size
     * @param bool                     $use_form
     *
     * @return static
     */
    public function addGridColumns(array $source, EnumDisplaySize|int|null $size = null, bool $use_form = false): static
    {
        // Validate source
        foreach ($source as $column) {
            if (!is_object($column) and !is_string($column)) {
                throw new OutOfBoundsException(tr('Invalid datatype for specified column. The column should be an object or a string, but is a ":datatype"', [
                    ':datatype' => gettype($column),
                ]));
            }

            $this->addGridColumn($column, $size, $use_form);
        }

        return $this;
    }


    /**
     * Add the specified column to this row
     *
     * @param object|string|null       $column
     * @param EnumDisplaySize|int|null $size
     * @param bool                     $use_form
     *
     * @return static
     */
    public function addGridColumn(object|string|null $column, EnumDisplaySize|int|null $size = null, bool $use_form = false): static
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
                $column = GridColumn::new()
                                    ->setContent($column)
                                    ->useForm($use_form);
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
