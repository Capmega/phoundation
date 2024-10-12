<?php

/**
 * QueryBuilder class
 *
 * This class helps building queries with multiple variables
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Databases\Sql\QueryBuilder;

use PDOStatement;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Interfaces\DataIteratorInterface;
use Phoundation\Data\Traits\TraitDataConnector;
use Phoundation\Data\Traits\TraitDataFilterForm;
use Phoundation\Data\Traits\TraitDataMetaEnabled;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Databases\Sql\QueryBuilder\Interfaces\QueryDefinitionsInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Strings;


class QueryBuilder extends QueryObject implements QueryBuilderInterface
{
    use TraitDataMetaEnabled;
    use TraitDataFilterForm;
    use TraitDataConnector;


    /**
     * The pre-defined query sections
     *
     * @var QueryDefinitionsInterface $definitions
     */
    protected QueryDefinitionsInterface $definitions;


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
     * Returns the bound variables execute array by reference, allowing outside changes
     *
     * @return array|null
     */
    public function &getExecuteByReference(): ?array
    {
        return $this->execute;
    }


    /**
     * Executes the query and returns a PDO statement
     *
     * @param bool $debug
     *
     * @return PDOStatement
     */
    public function execute(bool $debug = false): PDOStatement
    {
        return sql($this->o_connector)->query($this->getQuery($debug), $this->execute);
    }


    /**
     * Returns the complete query that can be executed
     *
     * @param bool $debug
     *
     * @return string
     */
    public function getQuery(bool $debug = false): string
    {
        $query = (($this->debug or $debug) ? ' ' : '');

        // Execute all predefined before executing the query
        foreach ($this->predefines as $predefine) {
            $predefine();
        }

        if ($this->select) {
            $query .= Strings::ensureEndsNotWith(trim(implode(', ', $this->select)), ',') . PHP_EOL . 'FROM `' . implode('`, `', $this->from) . '` ';

        } elseif ($this->delete) {
            $query .= Strings::ensureEndsNotWith(trim(implode(', ', $this->delete)), ',') . PHP_EOL . 'FROM `' . implode('`, `', $this->from) . '` ';

        } elseif ($this->update) {
            $query .= 'UPDATE `' . Strings::ensureEndsNotWith(trim(implode('`, `', $this->from)), ',') . '` SET ' . implode(',' . PHP_EOL, $this->update);
        }

        foreach ($this->joins as $join) {
            $query .= PHP_EOL . $join;
        }

        if ($this->wheres) {
            $query .= PHP_EOL . 'WHERE ' . implode(' AND ', $this->wheres);
        }

        if ($this->group_by) {
            $query .= PHP_EOL . 'GROUP BY ' . implode(', ', $this->group_by);
        }

        if ($this->having) {
            $query .= PHP_EOL . 'HAVING ' . implode(' AND ', $this->having);
        }

        if ($this->order_by) {
            $query .= PHP_EOL . 'ORDER BY ' . implode(', ', $this->order_by);
        }

        if ($this->limit_count) {
            $query .= PHP_EOL . 'LIMIT ' . $this->limit_offset . ', ' . $this->limit_count;
        }

        return $query;
    }


    /**
     * Executes the query and returns the single result
     *
     * @param bool $debug
     *
     * @return array|null
     */
    public function get(bool $debug = false): ?array
    {
        return sql($this->o_connector)->get($this->getQuery($debug), $this->execute, $this->meta_enabled);
    }


    /**
     * Executes the query and returns the single column from the single result
     *
     * @param string|null $column
     * @param bool        $debug
     *
     * @return string|float|int|bool|null
     */
    public function getColumn(?string $column = null, bool $debug = false): string|float|int|bool|null
    {
        return sql($this->o_connector)->getColumn($this->getQuery($debug), $this->execute, $column);
    }


    /**
     * Executes the query and returns the list of results
     *
     * @param bool $debug
     *
     * @return array
     */
    public function list(bool $debug = false): array
    {
        return sql($this->o_connector)->list($this->getQuery($debug), $this->execute);
    }


    /**
     * Returns true if this query builder object has all values built to generate a query
     *
     * @return bool
     */
    public function isBuilt(): bool
    {
        return $this->select and $this->wheres;
    }


    /**
     * Executes the parent::load() call
     *
     * @note Will cause an exception if the parent has not been set
     *
     * @return static
     * @throws OutOfBoundsException
     */
    public function load(): static
    {
        if (empty($this->parent)) {
            throw new OutOfBoundsException(tr('Cannot load parent data from query, no parent has been specified'));
        }

        $this->parent->load();
        return $this;
    }
}
