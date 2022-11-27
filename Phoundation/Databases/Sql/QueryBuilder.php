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
     * The build variables
     *
     * @var array $execute
     */
    protected array $execute = [];



    /**
     * queryBuilder class constructor
     */
    public function __construct()
    {
    }



    /**
     * Make this a SELECT query by adding the select clause here
     *
     * @param string $select
     * @return $this
     */
    public function addSelect(string $select): static
    {
        if ($this->delete) {
            throw new OutOfBoundsException(tr('DELETE part of query has already been added, cannot add SELECT'));
        }

        if ($this->update) {
            throw new OutOfBoundsException(tr('UPDATE part of query has already been added, cannot add SELECT'));
        }

        $this->select .= $select;
        return $this;
    }



    /**
     * Make this a DELETE query by adding the select clause here
     *
     * @param string $delete
     * @return $this
     */
    public function addDelete(string $delete): static
    {
        if ($this->select) {
            throw new OutOfBoundsException(tr('SELECT part of query has already been added, cannot add DELETE'));
        }

        if ($this->update) {
            throw new OutOfBoundsException(tr('UPDATE part of query has already been added, cannot add DELETE'));
        }

        $this->delete .= $delete;
        return $this;
    }



    /**
     * Make this a UPDATE query by adding the select clause here
     *
     * @param string $update
     * @return $this
     */
    public function addUpdate(string $update): static
    {
        if ($this->select) {
            throw new OutOfBoundsException(tr('SELECT part of query has already been added, cannot add UPDATE'));
        }

        if ($this->delete) {
            throw new OutOfBoundsException(tr('DELETE part of query has already been added, cannot add UPDATE'));
        }

        $this->update .= $update;
        return $this;
    }



    /**
     * Add the FROM part of the query
     *
     * @param string $from
     * @return $this
     */
    public function addFrom(string $from): static
    {
        if (isset($this->update)) {
            throw new OutOfBoundsException(tr('This is an UPDATE query, cannot add FROM'));
        }

        $this->from .= $from;
        return $this;
    }



    /**
     * Add a JOIN part of the query
     *
     * @param string $join
     * @return $this
     */
    public function addJoin(string $join): static
    {
        if ($join) {
            $this->joins[] = $join;
        }

        return $this;
    }



    /**
     * Add a WHERE part of the query
     *
     * @param string $where
     * @return $this
     */
    public function addWhere(string $where): static
    {
        if ($where) {
            $this->wheres[] = $where;
        }

        return $this;
    }



    /**
     * Add a JOIN part of the query
     *
     * @param string $column
     * @param string|int $value
     * @return $this
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
}