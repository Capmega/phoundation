<?php

declare(strict_types=1);

namespace Phoundation\Databases\Sql;

use PDOStatement;
use Phoundation\Core\Strings;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Interfaces\DataListInterface;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Exception\OutOfBoundsException;


/**
 * QueryBuilder class
 *
 * This class helps building queries with multiple variables
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
class QueryBuilder implements QueryBuilderInterface
{
    /**
     * Select part of query
     *
     * @var string $select
     */
    protected array $select = [];

    /**
     * Delete part of query
     *
     * @var array $delete
     */
    protected array $delete = [];

    /**
     * Update part of query
     *
     * @var array $update
     */
    protected array $update = [];

    /**
     * From part of query
     *
     * @var array $from
     */
    protected array $from = [];

    /**
     * Join part of query
     *
     * @var array $joins
     */
    protected array $joins = [];

    /**
     * Where part of query
     *
     * @var array $wheres
     */
    protected array $wheres = [];

    /**
     * Groupby part of query
     *
     * @var array $group_by
     */
    protected array $group_by = [];

    /**
     * Having part of query
     *
     * @var array $having
     */
    protected array $having = [];

    /**
     * Orderby part of query
     *
     * @var array $order_by
     */
    protected array $order_by = [];

    /**
     * The build variables
     *
     * @var array $execute
     */
    protected array $execute = [];

    /**
     * If specified, the query builder will attempt to update the internal loading query for this object
     *
     * @var DataEntryInterface|DataListInterface|null $parent
     */
    protected DataEntryInterface|DataListInterface|null $parent;


    /**
     * QueryBuilder class constructor
     *
     * @param DataEntryInterface|DataListInterface|null $parent
     */
    public function __construct(DataEntryInterface|DataListInterface|null $parent = null)
    {
        $this->parent = $parent;

        if ($this->parent) {
            // The first from will be the table from the parent class
            $this->addFrom($parent->getTable());
        }
    }


    /**
     * Make this a SELECT query by adding the select clause here
     *
     * @param string $select
     * @param array|null $execute
     * @return static
     */
    public function addSelect(string $select, ?array $execute = null): static
    {
        if ($this->delete) {
            throw new OutOfBoundsException(tr('DELETE part of query has already been added, cannot add SELECT'));
        }

        if ($this->update) {
            throw new OutOfBoundsException(tr('UPDATE part of query has already been added, cannot add SELECT'));
        }

        if (!$this->select) {
            $select = 'SELECT ' . $select;
        }

        $this->select[] = $select;
        return $this->addExecuteArray($execute);
    }


    /**
     * Make this a DELETE query by adding the select clause here
     *
     * @param string $delete
     * @param array|null $execute
     * @return static
     */
    public function addDelete(string $delete, ?array $execute = null): static
    {
        if ($this->select) {
            throw new OutOfBoundsException(tr('SELECT part of query has already been added, cannot add DELETE'));
        }

        if ($this->update) {
            throw new OutOfBoundsException(tr('UPDATE part of query has already been added, cannot add DELETE'));
        }

        if (!$this->delete) {
            $delete = 'DELETE ' . $delete;
        }

        $this->delete[] = $delete;
        return $this->addExecuteArray($execute);
    }


    /**
     * Make this a UPDATE query by adding the select clause here
     *
     * @param string $update
     * @param array|null $execute
     * @return static
     */
    public function addUpdate(string $update, ?array $execute = null): static
    {
        if ($this->select) {
            throw new OutOfBoundsException(tr('SELECT part of query has already been added, cannot add UPDATE'));
        }

        if ($this->delete) {
            throw new OutOfBoundsException(tr('DELETE part of query has already been added, cannot add UPDATE'));
        }

        $this->update[] = $update;
        return $this->addExecuteArray($execute);
    }


    /**
     * Add the FROM part of the query
     *
     * @param string $from
     * @param array|null $execute
     * @return static
     */
    public function addFrom(string $from, ?array $execute = null): static
    {
        $this->from[] = $from;

        return $this->addExecuteArray($execute);
    }


    /**
     * Add a JOIN part of the query
     *
     * @param string $join
     * @param array|null $execute
     * @return static
     */
    public function addJoin(string $join, ?array $execute = null): static
    {
        if ($join) {
            $this->joins[] = $join;
        }

        return $this->addExecuteArray($execute);
    }


    /**
     * Add a WHERE part of the query
     *
     * @param string $where
     * @param array|null $execute
     * @return static
     */
    public function addWhere(string $where, ?array $execute = null): static
    {
        if ($where) {
            $this->wheres[] = $where;
        }

        return $this->addExecuteArray($execute);
    }


    /**
     * Add a GROUP BY part of the query
     *
     * @param string $group_by
     * @param array|null $execute
     * @return static
     */
    public function addGroupBy(string $group_by, ?array $execute = null): static
    {
        if ($group_by) {
            $this->group_by[] = $group_by;
        }

        return $this->addExecuteArray($execute);
    }


    /**
     * Add a HAVING part of the query
     *
     * @param string $having
     * @param array|null $execute
     * @return static
     */
    public function addHaving(string $having, ?array $execute = null): static
    {
        if ($having) {
            $this->having[] = $having;
        }

        return $this->addExecuteArray($execute);
    }


    /**
     * Add a ORDER BY part of the query
     *
     * @param string $order_by
     * @param array|null $execute
     * @return static
     */
    public function addOrderBy(string $order_by, ?array $execute = null): static
    {
        if ($order_by) {
            $this->order_by[] = $order_by;
        }

        return $this->addExecuteArray($execute);
    }


    /**
     * Add a JOIN part of the query
     *
     * @param string $column
     * @param string|int|null $value
     * @return static
     */
    public function addExecute(string $column, string|int|null $value): static
    {
        $this->execute[Strings::startsWith($column, ':')] = $value;
        return $this;
    }


    /**
     * Returns a column comparison and adds the bound variable to the execute list
     *
     * @param string $column
     * @param array|string|int|null $value
     * @return string
     */
    public function compareQuery(string $column, array|string|int|null $value): string
    {
        switch (gettype($value)) {
            case 'NULL':
                return ' IS NULL ';

            case 'string':
                $this->execute[Strings::startsWith($column, ':')] = $value;
                return ' = :' . $column . ' ';

            case 'array':
                switch (count($value) == 1) {
                    case 0:
                        // Nothing here!
                        return '';

                    case 1:
                        // This is just a scalar, try again!
                        return $this->compareQuery($column, current($value));
                }

                $count   = 0;
                $columns = [];

                foreach ($value as $scalar) {
                    $this->execute[$column . $count++] = $scalar;
                    $columns[] = $column . ($count++);
                }

                return ' = IN (' . implode(', ', $columns) . ') ';
        }

        throw new OutOfBoundsException(tr('Unknown / unsupported datatype specified for value ":value"', [
            ':value' => $value
        ]));
    }


    /**
     * Returns the complete query that can be executed
     *
     * @param bool $debug
     * @return string
     */
    public function getQuery(bool $debug = false): string
    {
        $query = ($debug ? ' ' : '');

        if ($this->select) {
            $query .= implode(', ', $this->select) . ' FROM ' . implode(', ', $this->from) . ' ';

        } elseif ($this->delete) {
            $query .= implode(', ', $this->delete) . ' FROM ' . implode(', ', $this->from) . ' ';

        } elseif ($this->update) {
            $query .= 'UPDATE ' . implode(', ', $this->from) . ' SET ' . implode(', ', $this->update);
        }

        foreach ($this->joins as $join) {
            $query .= $join . ' ';
        }

        if ($this->wheres) {
            $query .= ' WHERE ' . implode(' AND ', $this->wheres);
        }

        if ($this->group_by) {
            $query .= ' GROUP BY ' . implode(' AND ', $this->group_by);
        }

        if ($this->having) {
            $query .= ' HAVING ' . implode(' AND ', $this->having);
        }

        if ($this->order_by) {
            $query .= ' ORDER BY ' . implode(', ', $this->order_by);
        }

        return $query;
    }


    /**
     * Returns the bound variables execute array
     *
     * @return array
     */
    public function getExecute(): array
    {
        return $this->execute;
    }


    /**
     * Executes the query and returns a PDO statement
     *
     * @return PDOStatement
     */
    public function execute(bool $debug = false): PDOStatement
    {
        return sql()->query($this->getQuery($debug), $this->getExecute());
    }


    /**
     * Add the specified execute array to the internal execute array
     *
     * @param array|null $execute
     * @return static
     */
    public function addExecuteArray(?array $execute): static
    {
        if ($execute) {
            foreach ($execute as $key => $value) {
                $this->addExecute($key, $value);
            }
        }

        return $this;
    }


    /**
     * Execute the built query and return the results as an array
     *
     * @return array
     */
    public function list(): array
    {
        return sql()->list($this->getQuery(), $this->getExecute());
    }


    /**
     * Execute the built query and return the required single row as an array
     *
     * @return array|null
     */
    public function get(): ?array
    {
        return sql()->get($this->getQuery(), $this->getExecute());
    }


    /**
     * Execute the built query and return the required single row as an array
     *
     * @return string|float|int|null
     */
    public function getColumn(): string|float|int|null
    {
        return sql()->getColumn($this->getQuery(), $this->getExecute());
    }
}