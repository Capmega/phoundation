<?php

/**
 * Trait TraitDataReadonly
 *
 * This adds readonly state registration to objects
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\DataEntries\Exception\DataEntryColumnReadonlyException;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;


trait TraitDataReadonlyColumns
{
    /**
     * Tracks columns that can only be set once
     *
     * @var array|null $readonly_columns
     */
    protected ?array $readonly_columns = null;


    /**
     * Returns the columns within this DataEntry object that are readonly
     *
     * @return array
     */
    public function getReadonlyColumns(): array
    {
        return $this->readonly_columns;
    }


    /**
     * Returns the columns within this DataEntry object that are readonly
     *
     * @param IteratorInterface|array|string $readonly_columns A list of all columns that are readonly
     *
     * @return static
     */
    public function setReadonlyColumns(IteratorInterface|array|string $readonly_columns): static
    {
        $this->readonly_columns = array_flip(Arrays::force($readonly_columns));
        return $this;
    }


    /**
     * Throws an exception for the given action if the specified object column is readonly
     *
     * When a column is readonly it can be modified only BEFORE it has been saved to the database
     *
     * @param string $column The column to test
     * @param string $action The action that was about to be executed if this column is not readonly
     *
     * @return static
     */
    public function checkColumnIsReadonly(string $column, string $action): static
    {
        if (!$this->isLoading()) {
            if (!$this->isResolvingVirtualColumn()) {
                if ($this->getColumnIsReadonly($column)) {
                    if (!$this->isNew()) {
                        throw DataEntryColumnReadonlyException::new(tr('Unable to perform action ":action", the column ":column" in the ":object" object is readonly', [
                            ':action' => $action,
                            ':column'  => $column,
                            ':object' => Strings::fromReverse(static::class, '\\'),
                        ]))->setData([
                            'column' => $column,
                        ]);
                    }
                }
            }
        }

        return $this;
    }


    /**
     * Returns if this column is readonly or not
     *
     * @param string $column The column that should be tested for readonly mode or not
     *
     * @return bool
     */
    public function columnIsReadonly(string $column): bool
    {
        if ($this->readonly_columns) {
            return array_key_exists($column, $this->readonly_columns);
        }

        return false;
    }


    /**
     * Returns if this object is readonly or not
     *
     * @param string $column The column to test
     *
     * @return bool
     */
    public function getColumnIsReadonly(string $column): bool
    {
        return $this->columnIsReadonly($column);
    }


    /**
     * Sets if this object is readonly or not
     *
     * @param string $column          The column to make readonly (or not)
     * @param bool   $readonly [true] Sets whether the column should be readonly or not
     *
     * @return static
     */
    public function setColumnIsReadonly(string $column, bool $readonly = true): static
    {
        if ($readonly) {
            if (empty($this->readonly_columns)) {
                $this->readonly_columns = [];
            }

            $this->readonly_columns[$column] = true;

        } else {
            unset($this->readonly_columns[$column]);

            if (empty($this->readonly_columns)) {
                // Set the variable back to null
                $this->readonly_columns = null;
            }
        }
        return $this;
    }
}
