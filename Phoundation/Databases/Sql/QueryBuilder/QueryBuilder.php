<?php

declare(strict_types=1);

namespace Phoundation\Databases\Sql\QueryBuilder;

use PDOStatement;
use Phoundation\Core\Strings;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Interfaces\DataListInterface;
use Phoundation\Data\Traits\DataDebug;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Databases\Sql\QueryBuilder\Interfaces\QueryDefinitionsInterface;
use Phoundation\Exception\OutOfBoundsException;
use function Phoundation\Databases\Sql\count;
use function Phoundation\Databases\Sql\gettype;


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
class QueryBuilder extends QueryObject implements QueryBuilderInterface
{
    /**
     * The pre-defined query sections
     *
     * @var QueryDefinitionsInterface $definitions
     */
    protected QueryDefinitionsInterface $definitions;


    /**
     * Returns the complete query that can be executed
     *
     * @param bool $debug
     * @return string
     */
    public function getQuery(bool $debug = false): string
    {
        $query = (($this->debug or $debug) ? ' ' : '');

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
            $query .= ' GROUP BY ' . implode(', ', $this->group_by);
        }

        if ($this->having) {
            $query .= ' HAVING ' . implode(' AND ', $this->having);
        }

        if ($this->order_by) {
            $query .= ' ORDER BY ' . implode(', ', $this->order_by);
        }

        if ($this->limit_count) {
            $query .= ' LIMIT ' . $this->limit_offset . ', ' . $this->limit_count;
        }

        return $query;
    }


    /**
     * Returns the bound variables execute array
     *
     * @return array|null
     */
    public function getExecute(): ?array
    {
        return $this->execute;
    }


    /**
     * Executes the query and returns a PDO statement
     *
     * @param bool $debug
     * @return PDOStatement
     */
    public function execute(bool $debug = false): PDOStatement
    {
        return sql()->query($this->getQuery($debug), $this->execute);
    }


    /**
     * Executes the query and returns the single result
     *
     * @param bool $debug
     * @return array|null
     */
    public function get(bool $debug = false): ?array
    {
        return sql()->get($this->getQuery($debug), $this->execute);
    }

    /**
     * Executes the query and returns the single column from the single result
     *
     * @param bool $debug
     * @return string|float|int|bool|null
     */
    public function getColumn(bool $debug = false): string|float|int|bool|null
    {
        return sql()->getColumn($this->getQuery($debug), $this->execute);
    }


    /**
     * Executes the query and returns the list of results
     *
     * @param bool $debug
     * @return array
     */
    public function list(bool $debug = false): array
    {
        return sql()->list($this->getQuery($debug), $this->execute);
    }
}