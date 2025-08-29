<?php

/**
 * QueryBuilder class
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

use PDOStatement;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\Enums\EnumLoadParameters;
use Phoundation\Data\Traits\TraitDataConnector;
use Phoundation\Data\Traits\TraitDataFilterForm;
use Phoundation\Data\Traits\TraitDataMetaEnabled;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Databases\Sql\QueryBuilder\Interfaces\QueryDefinitionsInterface;
use Phoundation\Databases\Sql\SqlQueries;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Forms\Interfaces\FilterFormInterface;


class QueryBuilder extends QueryObject implements QueryBuilderInterface
{
    use TraitDataMetaEnabled;
    use TraitDataFilterForm {
        setFilterFormObject as protected ___setFilterFormObject;
    }
    use TraitDataConnector;


    /**
     * The pre-defined query sections
     *
     * @var QueryDefinitionsInterface $o_definitions
     */
    protected QueryDefinitionsInterface $o_definitions;

    /**
     * Caches the executed query
     *
     * @var string|null $query
     */
    protected ?string $query = null;


    /**
     * Renders and returns a "... LIKE ... " query part for the query builder
     *
     * @param IdentifierInterface|array|string|int|null $identifiers
     * @param bool                                      $like
     *
     * @return static
     */
    public function setIdentifiers(IdentifierInterface|array|string|int|null $identifiers = null, bool $like = false): static
    {
        $this->clearWhere();

        $equal = ($like ? 'LIKE ' : '= ');

        if ($identifiers) {
            foreach ($identifiers as $key => $value) {
                // Make sure that the identifier column contains a table to avoid ambiguous columns
                if (!str_contains($key, '.')) {
                    // This key contains no table, default to the first FROM table
                    $key = $this->getFrom() . '.' . $key;
                }

                $key    = str_replace('.', '`.`', $key);
                $key    = str_replace('``.``', '`.`', $key);
                $key    = Strings::ensureBeginsWith($key, '`');
                $key    = Strings::ensureEndsWith($key, '`');
                $column = SqlQueries::makeColumn($key);

                $this->addWhere('' . $key . ' ' . $equal . ':' . $column)
                     ->addExecute($value, $column);
            }
        }

        return $this;
    }


    /**
     * Attaches the filter form object to this query builder for automatic filtering
     *
     * @param FilterFormInterface|null $filter_form
     *
     * @return static
     */
    public function setFilterFormObject(?FilterFormInterface $filter_form): static
    {
        $this->___setFilterFormObject($filter_form);
        $this->filter_form?->applyFiltersToQueryBuilder($this);

        return $this;
    }


    /**
     * Adds the specifications of the given query builder to the query builder of this DataEntry object
     *
     * @param QueryBuilderInterface|null $o_query_builder
     *
     * @return static
     */
    public function addQueryBuilderObject(?QueryBuilderInterface $o_query_builder): static
    {
        if ($o_query_builder) {
            $this->addSource($o_query_builder->getSource());
        }

        return $this;
    }


    /**
     * Returns the bound variables execute array
     *
     * @return array|null
     */
    public function getExecute(): ?array
    {
        return $this->executes;
    }


    /**
     * Returns the bound variables execute array by reference, allowing outside changes
     *
     * @return array|null
     */
    public function &getExecuteByReference(): ?array
    {
        return $this->executes;
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
        $this->query = $this->getQuery($debug);
        return sql($this->o_connector)->query($this->query, $this->executes);
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

        if ($this->selects) {
            $query .= 'SELECT ' . Strings::ensureEndsNotWith(trim(implode(', ', $this->selects)), ',') . PHP_EOL . 'FROM `' . implode('`, `', $this->froms) . '` ';

        } elseif ($this->delete) {
            if (empty($this->wheres)) {
                throw new QueryBuilderException(tr('Cannot create delete query without WHERE clauses'));
            }

            $query .= 'DELETE FROM `' . implode('`, `', $this->froms) . '` ';

        } elseif ($this->updates) {
            $query .= 'UPDATE `' . Strings::ensureEndsNotWith(trim(implode('`, `', $this->froms)), ',') . '` SET ' . implode(',' . PHP_EOL, $this->updates);
        }

        foreach ($this->joins as $join) {
            $query .= PHP_EOL . $join;
        }

        if ($this->wheres) {
            $query .= PHP_EOL . 'WHERE ' . implode(' AND ', $this->wheres);
        }

        if ($this->group_bys) {
            $query .= PHP_EOL . 'GROUP BY ' . implode(', ', $this->group_bys);
        }

        if ($this->havings) {
            $query .= PHP_EOL . 'HAVING ' . implode(' AND ', $this->havings);
        }

        if ($this->order_bys) {
            $query .= PHP_EOL . 'ORDER BY ' . implode(', ', $this->order_bys);
        }

        if ($this->limit_count) {
            $query .= PHP_EOL . 'LIMIT ' . $this->limit_offset . ', ' . $this->limit_count;
        }

        return $query;
    }


    /**
     * Returns a hash from the executed query
     *
     * @return string|null
     */
    public function getQueryHash(): ?string
    {
        if (empty($this->query)) {
            return null;
        }

        return sha1(sql()->parseQuery($this->query, $this->executes));
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
        return sql($this->o_connector)->getRow($this->getQuery($debug), $this->executes, $this->getMetaEnabled());
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
        return sql($this->o_connector)->getColumn($this->getQuery($debug), $this->executes, $column);
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
        return sql($this->o_connector)->list($this->getQuery($debug), $this->executes);
    }


    /**
     * Executes the query and returns array with each complete row in a subarray
     *
     * Each subarray will have a numeric index key starting from 0
     *
     * @param bool $debug
     *
     * @return array
     */
    public function listArray(bool $debug = false): array
    {
        return sql($this->o_connector)->listArray($this->getQuery($debug), $this->executes);
    }


    /**
     * Executes the single column query and returns array with only scalar values.
     *
     * Each key will be a numeric index starting from 0
     *
     * @param bool $debug
     *
     * @return array
     */
    public function listScalar(bool $debug = false): array
    {
        return sql($this->o_connector)->listScalar($this->getQuery($debug), $this->executes);
    }


    /**
     * Executes the query for two columns and will return the results as a key => static value array
     *
     * @param bool $debug
     *
     * @return array
     */
    public function listKeyValue(bool $debug = false): array
    {
        return sql($this->o_connector)->listKeyValue($this->getQuery($debug), $this->executes);
    }


    /**
     * Executes the query for two or more columns and will return the results as a key => values-in-array array
     *
     * The key will be the first selected column but will be included in the value array
     *
     * @param bool        $debug
     * @param string|null $column
     *
     * @return array
     */
    public function listKeyValues(bool $debug = false, ?string $column = null): array
    {
        return sql($this->o_connector)->listKeyValues($this->getQuery($debug), $this->executes, $column);
    }


    /**
     * Returns true if this query builder object has all values built to generate a query
     *
     * @return bool
     */
    public function isBuilt(): bool
    {
        return $this->selects and $this->wheres;
    }


    /**
     * Executes the parent::load() call
     *
     * @note Will cause an exception if the parent has not been set
     *
     * @param IdentifierInterface|array|string|int|null $identifier
     * @param EnumLoadParameters|null                   $on_load_null_identifier
     * @param EnumLoadParameters|null                   $on_load_not_exists
     *
     * @return static|null
     */
    public function load(IdentifierInterface|array|string|int|null $identifier = null, ?EnumLoadParameters $on_load_null_identifier = null, ?EnumLoadParameters $on_load_not_exists = null): ?static
    {
        if (empty($this->parent)) {
            throw new OutOfBoundsException(tr('Cannot load parent data from query, no parent has been specified'));
        }

        $this->parent->load($identifier, $on_load_null_identifier, $on_load_not_exists);
        return $this;
    }
}
