<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Forms;

use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Config;
use Phoundation\Web\Html\Components\Forms\Interfaces\DataEntryFormColumnInterface;
use Phoundation\Web\Html\Components\Forms\Interfaces\DataEntryFormInterface;
use Phoundation\Web\Html\Components\Forms\Interfaces\DataEntryFormRowsInterface;
use Phoundation\Web\Html\Components\Input\Interfaces\RenderInterface;


/**
 * Class DataEntryFormRows
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class DataEntryFormRows implements DataEntryFormRowsInterface
{
    /**
     * Tracks the renderer will automatically force a row each time the size 12 is passed
     *
     * @var bool $force_rows
     */
    protected static bool $force_rows = true;

    /**
     * A list of all the columns to render
     *
     * @var array $columns
     */
    protected array $columns = [];

    /**
     * The maximum number of columns per row
     *
     * @var int $columns_max
     */
    protected int $column_count = 12;

    /**
     * The DataEntryForm to be rendered
     *
     * @var DataEntryFormInterface|null $render_object
     */
    protected DataEntryFormInterface|null $render_object;


    /**
     * DataEntryFormColumn class constructor
     *
     * @param DataEntryFormInterface|null $render_object
     */
    public function __construct(DataEntryFormInterface|null $render_object)
    {
        $this->render_object = $render_object;
    }


    /**
     * Returns the component
     *
     * @param DataEntryFormInterface|null $render_object
     * @return static
     */
    public static function new(DataEntryFormInterface|null $render_object): static
    {
        return new static($render_object);
    }


    /**
     *  Returns if the renderer will automatically force a row each time the size 12 is passed
     *
     * @return bool
     */
    public static function getForceRows(): bool
    {
        return static::$force_rows;
    }


    /**
     *  Sets if the renderer will automatically force a row each time the size 12 is passed
     *
     * @param bool $force_rows
     * @return void
     */
    public static function setForceRows(bool $force_rows): void
    {
        static::$force_rows = $force_rows;
    }


    /**
     * Returns the maximum number of columns per row
     *
     * @return int
     */
    public function getColumnCount(): int
    {
        return $this->column_count;
    }


    /**
     * Sets the maximum number of columns per row
     *
     * @param int $count
     * @return $this
     */
    public function setColumnCount(int $count): static
    {
        if (($count < 1) or ($count > 12)) {
            throw new OutOfBoundsException(tr('Invalid column count ":count" specified, must be an integer number between 1 and 12', [
                ':count' => $count
            ]));
        }

        $this->column_count = $count;
        return $this;
    }


    /**
     * Adds the column component and its definition as a DataEntryFormColumn
     *
     * @param DefinitionInterface|null $definition
     * @param RenderInterface|string|null $component
     * @return $this
     */
    public function add(?DefinitionInterface $definition = null, RenderInterface|string|null $component = null): static
    {
        return $this->addColumn(DataEntryFormColumn::new()->setDefinition($definition)->setColumnComponent($component));
    }


    /**
     * Adds the specified DataEntryFormColumn to this DataEntryFormRow
     *
     * @param DataEntryFormColumnInterface $column
     * @return $this
     */
    public function addColumn(DataEntryFormColumnInterface $column): static
    {
        $this->columns[] = $column;
        return $this;
    }


    /**
     * Renders and returns the HTML for this component
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $column_count = $this->column_count;
        $render       = '';

        foreach ($this->columns as $column) {
            $definition = $column->getDefinition();

            if ($definition === null) {
                if ($column_count === $this->column_count) {
                    // No row is open right now
                    break;
                }

                // Close the row
                $column_count = 0;

            } else {
                if ($definition->getHidden()) {
                    // Hidden elements don't display anything beyond the hidden <input>
                    $render .= $column->render();
                }

                if (($definition->getSize() <= 0) or ($definition->getSize() > 12)) {
                    throw new OutOfBoundsException(tr('Cannot render DataEntryForm ":class" because the definition for column ":column" has invalid size ":size", it must be an integer number between 1 and 12', [
                        ':size'   => $definition->getSize(),
                        ':column' => $column,
                        ':class'  => get_class($this->render_object)
                    ]));
                }

                $cols[] = $definition->getLabel() . ' = "' . $definition->getColumn() . '" [' . $definition->getSize() . ']';

                // Keep track of column size, close each row when size 12 is reached
                if (static::$force_rows) {
                    if ($column_count == $this->column_count) {
                        // Open a new row
                        $render .= '<div class="row">';

                    } elseif ($definition->getSize() > $column_count) {
                        // This item is going to overflow the row, close the current row and open a new one.
                        $render      .= '</div><div class="row">';
                        $column_count = $this->column_count;
                    }
                }

                $render       .= $column->render();
                $column_count -= $definition->getSize();

                if ($column_count < 0) {
                    throw OutOfBoundsException::new(tr('Cannot add column ":label" for table / class ":class" form with size ":size", the row would surpass size 12 by ":count"', [
                        ':class' => $this->render_object?->getDefinitions()->getTable(),
                        ':label' => $definition->getLabel() . ' [' . $definition->getColumn() . ']',
                        ':size'  => abs($definition->getSize()),
                        ':count' => abs($column_count),
                    ]))->setData([
                        'Columns on this row' => $cols,
                        'HTML so far'         => $render
                    ]);
                }
            }

            if ($column_count == 0) {
                // Close the row
                $column_count = $this->column_count;
                $cols = [];

                if (static::$force_rows) {
                    $render .= '</div>';
                }
            }
        }

        if (static::$force_rows) {
            if ($column_count < $this->column_count) {
                // A row is still open, close it first
                $render .= '</div>';
            }

            return $render;
        }

        // Return all columns in one row
        return '<div class="row">' . $render . '</div>';
    }
}
