<?php

namespace Phoundation\Databases\Sql;

use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;




/**
 * queryBuilder class
 *
 * This class helps building queries with multiple variables
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
class QueryBuilder
{
    /**
     * Select part of query
     *
     * @var string $select
     */
    protected string $select = '';

    /**
     * Delete part of query
     *
     * @var string $delete
     */
    protected string $delete = '';

    /**
     * Update part of query
     *
     * @var string $update
     */
    protected string $update = '';

    /**
     * From part of query
     *
     * @var string $from
     */
    protected string $from = '';

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

        $this->select .= $select;
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

        $this->delete .= $delete;
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

        $this->update .= $update;
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
        if ($this->update) {
            throw new OutOfBoundsException(tr('This is an UPDATE query, cannot add FROM'));
        }

        $this->from .= $from;

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
     * @param string|int $value
     * @return static
     */
    public function addExecute(string $column, string|int $value): static
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
            case 'null':
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
            $query .= $this->select . ' ';

        } elseif ($this->update) {
            $query .= $this->update . ' ';

        } elseif ($this->delete) {
            $query .= $this->update . ' ';
        }

        $query .= $this->from . ' ';

        foreach ($this->joins as $join) {
            $query .= $join . ' ';
        }

        foreach ($this->wheres as $where) {
            $query .= $where . ' ';
        }

        foreach ($this->group_by as $group_by) {
            $query .= $group_by . ' ';
        }

        foreach ($this->having as $having) {
            $query .= $having . ' ';
        }

        foreach ($this->order_by as $order_by) {
            $query .= $order_by . ' ';
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



}