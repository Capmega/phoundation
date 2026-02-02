<?php

/**
 * QueryObject class
 *
 * This class helps building queries with multiple variables
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Databases\Sql\QueryBuilder;

use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntries\Interfaces\DataIteratorInterface;
use Phoundation\Data\Traits\TraitDataDebug;
use Phoundation\Databases\Sql\QueryBuilder\Interfaces\QueryObjectInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Strings;


class QueryObject implements QueryObjectInterface
{
    use TraitDataDebug;


    /**
     * @var int $limit_offset
     */
    protected int $limit_offset = 0;

    /**
     * @var int $limit_count
     */
    protected int $limit_count = 0;

    /**
     * @var int $int_count
     */
    protected int $in_count = 0;

    /**
     * Select part of query
     *
     * @var array $selects
     */
    protected array $selects = [];

    /**
     * Delete query
     *
     * @var bool $delete
     */
    protected bool $delete = false;

    /**
     * Update part of query
     *
     * @var array $updates
     */
    protected array $updates = [];

    /**
     * From part of query
     *
     * @var array $froms
     */
    protected array $froms = [];

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
     * @var array $group_bys
     */
    protected array $group_bys = [];

    /**
     * Having part of query
     *
     * @var array $havings
     */
    protected array $havings = [];

    /**
     * Orderby part of query
     *
     * @var array $order_bys
     */
    protected array $order_bys = [];

    /**
     * Predefined columns
     *
     * @var array $predefines
     */
    protected array $predefines = [];

    /**
     * The build variables
     *
     * @var array|null $executes
     */
    protected ?array $executes = null;


    /**
     * If specified, the query builder will attempt to update the internal loading query for this object
     *
     * @var DataEntryInterface|DataIteratorInterface|null $parent
     */
    protected DataEntryInterface|DataIteratorInterface|null $parent;


    /**
     * QueryObject class constructor
     *
     * @param DataEntryInterface|DataIteratorInterface|null $parent
     */
    public function __construct(DataEntryInterface|DataIteratorInterface|null $parent = null)
    {
        $this->parent = $parent;

        if ($this->parent) {
            // The first from will be the table from the parent class
            $this->setFrom($parent->getTable());
        }
    }


    /**
     * QueryObject class constructor
     *
     * @param DataEntryInterface|DataIteratorInterface|null $parent
     *
     * @return static
     */
    public static function new(DataEntryInterface|DataIteratorInterface|null $parent = null): static
    {
        return new static($parent);
    }


    /**
     * Resets all query variables
     *
     * @return static
     */
    public function reset(): static
    {
        $this->selects    = [];
        $this->froms      = [];
        $this->wheres     = [];
        $this->joins      = [];
        $this->group_bys  = [];
        $this->executes   = [];
        $this->predefines = [];
        $this->order_bys  = [];
        $this->delete     = false;

        return $this;
    }


    /**
     * Updates the source data from this QueryObject with the specified data
     *
     * @param array $source
     *
     * @return static
     */
    public function addSource(array $source): static
    {
        $this->selects    = array_merge($this->selects    ?? [], array_get_safe($source, 'selects'   , []));
        $this->froms      = array_merge($this->froms      ?? [], array_get_safe($source, 'froms'     , []));
        $this->wheres     = array_merge($this->wheres     ?? [], array_get_safe($source, 'wheres'    , []));
        $this->joins      = array_merge($this->joins      ?? [], array_get_safe($source, 'joins'     , []));
        $this->group_bys  = array_merge($this->group_bys  ?? [], array_get_safe($source, 'group_bys' , []));
        $this->executes   = array_merge($this->executes   ?? [], array_get_safe($source, 'executes'  , []));
        $this->updates    = array_merge($this->updates    ?? [], array_get_safe($source, 'updates'   , []));
        $this->predefines = array_merge($this->predefines ?? [], array_get_safe($source, 'predefines', []));
        $this->order_bys  = array_merge($this->order_bys  ?? [], array_get_safe($source, 'order_bys' , []));

        return $this;
    }


    /**
     * Updates the source data from this QueryObject with the specified data
     *
     * @param array $source
     *
     * @return static
     */
    public function setSource(array $source): static
    {
        $this->selects    = array_get_safe($source, 'selects');
        $this->froms      = array_get_safe($source, 'froms');
        $this->wheres     = array_get_safe($source, 'wheres');
        $this->joins      = array_get_safe($source, 'joins');
        $this->group_bys  = array_get_safe($source, 'group_bys');
        $this->executes   = array_get_safe($source, 'executes');
        $this->delete     = array_get_safe($source, 'delete');
        $this->updates    = array_get_safe($source, 'updates');
        $this->predefines = array_get_safe($source, 'predefines');
        $this->order_bys  = array_get_safe($source, 'order_bys');

        return $this;
    }


    /**
     * Returns the source of this object
     *
     * @note: This object doesn't work with "source" data as such, so it will be constructed upon request
     *
     * @return array
     */
    public function getSource(): array
    {
        return [
            'selects'    => $this->selects,
            'froms'      => $this->froms,
            'wheres'     => $this->wheres,
            'joins'      => $this->joins,
            'group_bys'  => $this->group_bys,
            'executes'   => $this->executes,
            'delete'     => $this->delete,
            'updates'    => $this->updates,
            'predefines' => $this->predefines,
            'order_bys'  => $this->order_bys
        ];
    }


    /**
     * Returns the first FROM table
     *
     * @return string|null
     */
    public function getFrom(): ?string
    {
        return array_value_first($this->froms);
    }


    /**
     * Returns the WHERE parts of the query
     *
     * @return array
     */
    public function getFroms(): array
    {
        return $this->froms;
    }


    /**
     * Sets the "FROM" part of the query
     *
     * @param string|null $from
     * @param array|null  $execute
     *
     * @return static
     */
    public function setFrom(?string $from, ?array $execute = null): static
    {
        $this->froms = [];
        return $this->addFrom($from, $execute);
    }


    /**
     * Add the "FROM" part of the query
     *
     * @param string|null $from
     * @param array|null  $execute
     *
     * @return static
     */
    public function addFrom(?string $from, ?array $execute = null): static
    {
        $this->froms[] = $from;
        return $this->addExecuteArray($execute);
    }


    /**
     * Add the specified execute array to the internal execute array
     *
     * @param array|null $execute
     *
     * @return static
     */
    public function addExecuteArray(?array $execute): static
    {
        if ($execute) {
            foreach ($execute as $key => $value) {
                $this->addExecute($value, $key);
            }
        }

        return $this;
    }


    /**
     * Sets bound execution variables
     *
     * @param array $executes
     *
     * @return static
     */
    public function setExecutes(array $executes): static
    {
        $this->executes = $executes;

        return $this;
    }


    /**
     * Add bound execution variables
     *
     * @param string|float|int|null $value
     * @param string                $column
     *
     * @return static
     */
    public function addExecute(string|float|int|null $value, string $column): static
    {
        if (!$this->executes) {
            $this->executes = [];
        }

        $this->executes[Strings::ensureBeginsWith($column, ':')] = $value;

        return $this;
    }


    /**
     * Returns the WHERE parts of the query
     *
     * @return array
     */
    public function getSelects(): array
    {
        return $this->selects;
    }


    /**
     * Make this a SELECT query by adding the select clause here
     *
     * @param string|null $select
     * @param array|null  $execute
     *
     * @return static
     */
    public function setSelects(?string $select, ?array $execute = null): static
    {
        $this->selects = [];

        return $this->addSelect($select, $execute);
    }


    /**
     * Make this a SELECT query by adding the select clause here
     *
     * @param string|null $select
     * @param array|null  $execute
     *
     * @return static
     */
    public function addSelect(?string $select, ?array $execute = null): static
    {
        if ($this->delete) {
            throw new OutOfBoundsException(tr('Cannot add SELECT to a DELETE query', []));
        }

        if ($this->updates) {
            throw new OutOfBoundsException(tr('UPDATE part of query has already been added, cannot add SELECT', []));
        }

        if ($select) {
            $this->selects[] = $select;
        }

        return $this->addExecuteArray($execute);
    }


    /**
     * Make this a DELETE query by adding the select clause here
     *
     * @param bool $delete
     *
     * @return static
     */
    public function setDelete(bool $delete): static
    {
        if ($this->selects) {
            throw new OutOfBoundsException(tr('SELECT part of query has already been added, cannot add DELETE', []));
        }

        if ($this->updates) {
            throw new OutOfBoundsException(tr('UPDATE part of query has already been added, cannot add DELETE', []));
        }

        $this->delete = $delete;
        return $this;
    }


    /**
     * Make this an UPDATE query by adding the select clause here
     *
     * @param string|null $update
     * @param array|null  $execute
     *
     * @return static
     */
    public function addUpdate(?string $update, ?array $execute = null): static
    {
        if ($this->selects) {
            throw new OutOfBoundsException(tr('SELECT part of query has already been added, cannot add UPDATE', []));
        }

        if ($this->delete) {
            throw new OutOfBoundsException(tr('DELETE part of query has already been added, cannot add UPDATE', []));
        }

        if ($update) {
            $this->updates[] = $update;
        }

        return $this->addExecuteArray($execute);
    }


    /**
     * Add a JOIN part of the query
     *
     * @param string|null $join
     * @param array|null  $execute
     *
     * @return static
     */
    public function addJoin(?string $join, ?array $execute = null): static
    {
        if ($join) {
            $this->joins[] = $join;
        }

        return $this->addExecuteArray($execute);
    }


    /**
     * Returns the JOINS parts of the query
     *
     * @return array
     */
    public function getJoins(): array
    {
        return $this->joins;
    }


    /**
     * Returns the WHERE parts of the query
     *
     * @return array
     */
    public function getWheres(): array
    {
        return $this->wheres;
    }


    /**
     * Sets the WHERE part of the query
     *
     * @param string|null $where
     * @param array|null  $execute
     *
     * @return static
     */
    public function setWhere(?string $where, ?array $execute = null): static
    {
        $this->wheres = [];

        return $this->addWhere($where, $execute);
    }


    /**
     * Add a WHERE part of the query
     *
     * @param string|null $where
     * @param array|null  $execute
     *
     * @return static
     */
    public function addWhere(?string $where, ?array $execute = null): static
    {
        if ($where) {
            $this->wheres[] = $where;
        }

        return $this->addExecuteArray($execute);
    }


    /**
     * Clears the "WHERE" section
     *
     * @return static
     */
    public function clearWhere(): static
    {
        $this->wheres = [];
        return $this;
    }


    /**
     * Returns the GROUP BY parts of the query
     *
     * @return array
     */
    public function getGroupBys(): array
    {
        return $this->group_bys;
    }


    /**
     * Add a GROUP BY part of the query
     *
     * @param string|null $group_by
     * @param array|null  $execute
     *
     * @return static
     */
    public function addGroupBy(?string $group_by, ?array $execute = null): static
    {
        if ($group_by) {
            $this->group_bys[] = $group_by;
        }

        return $this->addExecuteArray($execute);
    }


    /**
     * Returns the HAVING parts of the query
     *
     * @return array
     */
    public function getHavings(): array
    {
        return $this->havings;
    }


    /**
     * Add a HAVING part of the query
     *
     * @param string|null $having
     * @param array|null  $execute
     *
     * @return static
     */
    public function addHaving(?string $having, ?array $execute = null): static
    {
        if ($having) {
            $this->havings[] = $having;
        }

        return $this->addExecuteArray($execute);
    }


    /**
     * Returns the ORDER BY parts of the query
     *
     * @return array
     */
    public function getOrderBys(): array
    {
        return $this->order_bys;
    }


    /**
     * Sets the ORDER BY part of the query
     *
     * @param string|null $order_by
     * @param array|null  $execute
     *
     * @return static
     */
    public function setOrderBys(?string $order_by, ?array $execute = null): static
    {
        $this->order_bys = [];

        return $this->addOrderBy($order_by, $execute);
    }


    /**
     * Add a ORDER BY part of the query
     *
     * @param string|null $order_by
     * @param array|null  $execute
     *
     * @return static
     */
    public function addOrderBy(?string $order_by, ?array $execute = null): static
    {
        if ($order_by) {
            $this->order_bys[] = $order_by;
        }

        return $this->addExecuteArray($execute);
    }


    /**
     * Add a LIMIT part of the query
     *
     * @param int $count
     * @param int $offset
     *
     * @return static
     */
    public function setLimit(int $count, int $offset = 0): static
    {
        $this->limit_count  = $count;
        $this->limit_offset = $offset;

        return $this;
    }


    /**
     * Returns a column comparison and adds the bound variable to the execute list
     *
     * @param string                $column
     * @param array|string|int|null $value
     *
     * @return string
     */
    public function compareQuery(string $column, array|string|int|null $value): string
    {
        switch (gettype($value)) {
            case 'NULL':
                return ' IS NULL ';

            case 'integer':
                return $this->compareQuery($column, (string) $value);

            case 'string':
                $this->executes[Strings::ensureBeginsWith($column, ':')] = $value;
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
                    $this->executes[$column . $count++] = $scalar;
                    $columns[]                         = $column . ($count++);
                }

                return ' = IN (' . implode(', ', $columns) . ') ';
        }

        throw new OutOfBoundsException(tr('Unknown / unsupported datatype specified for value ":value"', [
            ':value' => $value,
        ]));
    }


    /**
     * Adds a WHERE = or WHERE IN depending on parameter type
     *
     * @param string                $column
     * @param array|string|int|null $value
     *
     * @return static
     */
    public function addWhereIn(string $column, array|string|int|null $value): static
    {
        $this->in_count++;

        switch (gettype($value)) {
            case 'NULL':
                return $this;

            case 'integer':
            case 'string':
                return $this->addWhere($column . ' = :value' . $this->in_count, ['value'. $this->in_count => $value]);

            case 'array':
                switch (count($value)) {
                    case 0:
                        return $this;
                    case 1:
                        return $this->addWhereIn($column, current($value));
                }

                return $this->addWhere($column . ' IN (' . implode(', ', $value) . ') ');
        }

        throw new OutOfBoundsException(tr('Unknown / unsupported datatype specified for value ":value"', [
            ':value' => $value,
        ]));
    }


    /**
     * Returns all predefines for this query builder
     *
     * @return array
     */
    public function getPredefines(): array
    {
        return $this->predefines;
    }


    /**
     * Add the specified predefined column
     *
     * @param string   $name
     * @param callable $callback
     *
     * @return static
     */
    public function addPredefine(string $name, callable $callback): static
    {
        $this->predefines[$name] = $callback;

        return $this;
    }
}
