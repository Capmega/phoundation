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
use Phoundation\Core\Core;
use Phoundation\Core\Log\Exception\LogException;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\Enums\EnumLoadParameters;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Traits\TraitDataConnector;
use Phoundation\Data\Traits\TraitDataFilterForm;
use Phoundation\Data\Traits\TraitDataMetaEnabled;
use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Databases\Sql\Paging;
use Phoundation\Databases\Sql\QueryBuilder\Interfaces\QueryDefinitionsInterface;
use Phoundation\Developer\Debug\Debug;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Forms\Interfaces\FilterFormInterface;
use Stringable;


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
     * Renders and returns a " WHERE ... " query part for the query builder
     *
     * @param IdentifierInterface|array|string|int|null $identifiers
     * @param bool                                      $like
     *
     * @return static
     */
    public function setIdentifiers(IdentifierInterface|array|string|int|null $identifiers = null, bool $like = false, bool $negative = false): static
    {
        $this->clearWhere();

        if ($identifiers) {
            foreach ($identifiers as $key => $value) {
                // Make sure that the identifier column contains a table to avoid ambiguous columns
                if (!str_contains($key, '.')) {
                    // This key contains no table, default to the first FROM table
                    $key = $this->getFrom() . '.' . $key;
                }

                $key    = str_replace('.'    , '`.`', $key);
                $key    = str_replace('``.``', '`.`', $key);
                $key    = Strings::ensureBeginsWith($key, '`');
                $key    = Strings::ensureEndsWith($key, '`');
show($key);
show($value);
                $where  = QueryBuilder::buildComparison($key, $value, $execute, $like, $negative);
show($where);

                $this->addWhere($where)
                     ->addExecuteArray($execute);
            }
        }
show($this->getQuery());
show($execute);
showdie($execute);
        return $this;
    }


    /**
     * Builds a comparison section for the specified column / value
     *
     * @param string                                             $column           The column to build the query part for
     * @param IteratorInterface|array|string|float|int|bool|null $value            The value to build the query part with
     * @param array|null                                         $execute          The execution variables. Passed by reference as it will modify the array
     * @param bool                                               $like     [false] If true, will use LIKE to compare, instead of =
     * @param bool                                               $negative [false] If true, will build a negative comparison (NOT IN, !=, NOT LIKE)
     *
     * @return string
     */
    protected static function buildComparison(string $column, IteratorInterface|array|string|float|int|bool|null $value, ?array &$execute, bool $like = false, bool $negative = false): string
    {
        if (is_array($value) or ($value instanceof IteratorInterface)) {
            // Build a comparison for a list of values
            $label = QueryBuilder::getLabel($column);
            $value = Arrays::force($value);

            if (!$like) {
                // Add the new values to the specified $execute array
                $in      = QueryBuilder::in($value, ':' . $label);
                $execute = array_merge($execute ?? [], $in);

                // Add the query section
                return $column . ' ' . ($negative ? 'NOT IN ' : 'IN ') . '(' . implode(',', array_keys($in)) . ')';
            }

            // LIKE queries cannot be combined with IN, make separate OR sections instead
            $return = [];
            $count  = 0;

            foreach ($value as $sub_value) {
                $return[] = QueryBuilder::buildComparison($column . $count++, $sub_value, $execute, true, $negative);
            }

            return implode(' OR ', $return);
        }

        return QueryBuilder::buildComparisonForScalar($column, $value, $execute, $like, $negative);
    }


    /**
     * Builds a comparison section for the specified column / value, but value must be scalar
     *
     * @param string                     $column           The column to build the query part for
     * @param string|float|int|bool|null $value            The value to build the query part with
     * @param array|null                 $execute          The query execution variables. Passed by reference as it will modify the array
     * @param bool                       $like     [false] If true, will use LIKE to compare, instead of =
     * @param bool                       $negative [false] If true, will build a negative comparison (NOT IN, !=, NOT LIKE)
     *
     * @return string
     */
    protected static function buildComparisonForScalar(string $column, string|float|int|bool|null $value, ?array &$execute, bool $like = false, bool $negative = false): string
    {
        // TODO This can cause SEVERE issues with values that start with a ! by themselves....
        if (str_starts_with((string) $value, '!')) {
            $negative = true;
            $value    = substr($value, 1);
        }

        if ($value === 'NULL') {
            $value = null;
        }

        if ($value === null) {
            return  $column . ' IS NULL';
        }

        $label           = ':' . QueryBuilder::getLabel($column);
        $execute[$label] = $value;

        if ($like) {
            if ($negative) {
                return  $column . ' NOT LIKE ' . $label;
            }

            return  $column . ' LIKE ' . $label;

        } elseif ($negative) {
            return  $column . ' != ' . $label;
        }

        return  $column . ' = ' . $label;
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
        if (empty($this->froms)) {
            throw new QueryBuilderException(tr('Cannot build query, no "FROM" tables specified'));
        }

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

        if ($this->select) {
            $query .= 'SELECT ' . Strings::ensureEndsNotWith(trim(implode(', ', $this->select)), ',') . PHP_EOL . 'FROM `' . implode('`, `', $this->froms) . '` ';

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
        return $this->select and $this->wheres;
    }


    /**
     * Executes the parent::load() call
     *
     * @note Will cause an exception if the parent has not been set
     *
     * @param IdentifierInterface|array|string|int|null $identifier
     * @param EnumLoadParameters|null                   $on_null_identifier
     * @param EnumLoadParameters|null                   $on_not_exists
     *
     * @return static|null
     * @todo Improve this method, DataIterator objects have a $like argument that  is not passed here?
     */
    public function load(IdentifierInterface|array|string|int|null $identifier = null, ?EnumLoadParameters $on_null_identifier = null, ?EnumLoadParameters $on_not_exists = null): ?static
    {
        if (empty($this->_parent)) {
            throw new OutOfBoundsException(tr('Cannot load parent data from query, no parent has been specified'));
        }

        if ($this->_parent instanceof DataEntryInterface) {
            $this->_parent->load($identifier, $on_null_identifier, $on_not_exists);

        } else {
            $this->_parent->load($identifier);
        }

        return $this;
    }


    /**
     * Returns the specified key in a format that can be used as query column identifiers
     *
     * @param string $key
     *
     * @return string
     */
    public static function getLabel(string $key): string
    {
        return str_replace([' ', '.', '-', '`', '"', "'", ''], '_', str_replace(['`'], '', $key));
    }


    /**
     * Ensures that the specified source string is surrounded by quotes
     *
     * @param Stringable|string|null $source     The source string to manipulate
     * @param string                 $quote  [`] The quote to use, defaults to ` (backtick)
     *
     * @return string
     */
    public static function ensureQuotes(Stringable|string|null $source, string $quote = '`'): string
    {
        return Strings::ensureSurroundedWith($source, $quote);
    }
































































    /**
     * Return a list of the specified $columns from the specified source
     *
     * @param array|string|null $source
     * @param string|null       $prefix
     * @param string            $id_column
     *
     * @return string
     */
    public static function getUpdateKeyValues(array|string|null $source, ?string $prefix = null, string $id_column = 'id'): string
    {
        if (is_string($source)) {
            // The source has already been prepared, return it
            return $source;
        }

        $return = [];

        foreach ($source as $key => $value) {
            switch ($key) {
                case $id_column:
                    // no break
                case 'meta_id':
                    // NEVER update these!
                    break;
                default:
                    $return[] = '`' . $key . '` = :' . $prefix . $key;
            }
        }

        return implode(', ', $return);
    }


    /**
     * Return a list of the specified $columns from the specified source
     *
     * @param array|string|null $where
     * @param string|null       $prefix
     * @param string            $separator
     *
     * @return string
     */
    public static function whereColumns(array|string|null $where, ?string $prefix = null, string $separator = ' AND '): string
    {
        if (!$where) {
            return '';
        }

        if (is_string($where)) {
            // The Source has already been prepared, return it
            return $where;
        }

        $return = [];

        foreach ($where as $key => $value) {
            switch ($key) {
                case 'meta_id':
                    // NEVER update these!
                    break;
                default:
                    $return[] = '`' . $prefix . $key . '` = :' . $key;
            }
        }

        return ' WHERE ' . implode($separator, $return);
    }


    /**
     * Return a list of the specified $columns from the specified source
     *
     * @param array  $source
     * @param string $separator
     *
     * @return string
     */
    public static function filterColumns(array $source, string $separator = ' AND '): string
    {
        $return = [];

        foreach ($source as $key => $value) {
            if (is_array($value)) {
                $list = [];

                foreach ($value as $subkey => $subvalue) {
                    $list[] = ':' . $key . $subkey;
                }

                $return[] = '`' . $key . '` IN (' . implode(',', $list) . ') ';

            } else {
                $return[] = '`' . $key . '` = :' . $key;
            }
        }

        return implode($separator, $return);
    }


    /**
     * Return a list of columns with prefixes from the keys of the specified source array
     *
     * @param array       $source
     * @param string|null $prefix
     *
     * @return string
     */
    public static function getPrefixedColumns(array $source, ?string $prefix = null): string
    {
        $return = [];

        foreach ($source as $key => $value) {
            $return[] = '`' . $prefix . $key . '`';
        }

        return implode(', ', $return);
    }


    /**
     * Converts the specified row data into a PDO bound variables compatible key > values array
     *
     * @param array|string $source
     * @param string|null  $prefix
     *
     * @return string
     */
    public static function getBoundKeys(array|string $source, ?string $prefix = null): string
    {
        $return = [];

        foreach ($source as $key => $value) {
            $return[':' . $prefix . $key] = $value;
        }

        $return = array_keys($return);
        $return = implode(', ', $return);

        return $return;
    }


    /**
     * Converts the specified row data into a PDO bound variables compatible key > values array
     *
     * @param array|null  $source
     * @param string|null $prefix
     * @param bool        $insert
     * @param array|null  $skip
     *
     * @return array
     */
    public static function getBoundValues(?array $source, ?string $prefix = null, bool $insert = false, ?array $skip = null): array
    {
        $return = [];

        if ($source) {
            foreach ($source as $key => $value) {
                if (($key === 'meta_id') and !$insert) {
                    // Only process meta_id on insert operations
                    continue;
                }

                if ($skip and in_array($key, $skip)) {
                    // Do not make a bound variable for this one
                    continue;
                }

                if (is_array($value)) {
                    foreach ($value as $subkey => $subvalue) {
                        $return[':' . $prefix . $key . $subkey] = $subvalue;
                    }

                } else {
                    $return[':' . $prefix . $key] = $value;
                }
            }
        }

        return $return;
    }


    /**
     * Use correct SQL in case NULL is used in queries
     *
     * @param string                      $column
     * @param array|string|int|float|null $values
     * @param string|null                 $label
     * @param array|null                  $execute
     * @param string                      $glue
     *
     * @return string
     */
    public static function is(string $column, array|string|int|float|null $values, ?string $label = null, ?array &$execute = null, string $glue = 'AND'): string
    {
        Arrays::ensure($execute);

        $label  = Strings::ensureBeginsWith($label ?? $column, ':');
        $return = [];

        if (is_array($values)) {
            $in    = [];
            $notin = [];

            foreach ($values as $value) {
                $not = false;

                if (str_starts_with((string) $value, '!')) {
                    // Make comparison NOT by prefixing ! to $value
                    $value = substr($value, 1);
                    $not   = true;
                }

                if (($value === null) or (strtoupper($value) === 'NULL')) {
                    $null = ($not ? '!NULL' : 'NULL');
                    continue;
                }

                if ($not) {
                    $notin[] = $value;

                } else {
                    $in[] = $value;
                }
            }

            if ($in) {
                $in       = static::in($in);
                $execute  = array_merge((array) $execute, $in);

                if (isset($null)) {
                    $return[] = ' (' . $column . ' IN (' . implode(', ', array_keys($in)) . ') 
                                OR ' . static::isSingle($column, $null, $label, $execute) . ')';

                } else {
                    $return[] = ' ' . $column . ' IN (' . implode(', ', array_keys($in)) . ')';
                }
            }

            if ($notin) {
                $notin   = static::in($notin, start: count($execute));
                $execute = array_merge((array) $execute, $notin);

                if (!isset($null)) {
                    $return[] = ' (' . $column . ' NOT IN (' . implode(', ', array_keys($notin)) . ') OR ' . $column . ' IS NULL)';

                } else {
                    $return[] = ' ' . $column . ' NOT IN (' . implode(', ', array_keys($notin)) . ')';
                }
            }

            if (isset($query_builder)) {
                $query_builder->setExecute($execute);
            }

            return implode(' ' . $glue . ' ', $return);
        }

        return static::isSingle($column, $values, $label, $execute);
    }


    /**
     * Return a sequential array that can be used in $this->in
     *
     * @param array|string $source
     * @param string       $column
     * @param bool         $filter_null
     * @param bool         $null_string
     * @param int          $start
     *
     * @return array
     */
    public static function in(array|string $source, string $column = ':value', bool $filter_null = false, bool $null_string = false, int $start = 0): array
    {
        if (empty($source)) {
            throw new OutOfBoundsException(tr('Specified source is empty'));
        }

        $column = Strings::ensureBeginsWith($column, ':');
        $source = Arrays::force($source);

        return Arrays::sequentialKeys($source, $column, $filter_null, $null_string, $start);
    }


    /**
     * Use correct SQL in case NULL is used in queries
     *
     * @param string                $column
     * @param string|int|float|null $value
     * @param string                $label
     * @param array|null            $execute
     *
     * @return string
     */
    public static function isSingle(string $column, string|int|float|null $value, string $label, ?array &$execute = null): string
    {
        $not = false;

        if (str_starts_with((string) $value, '!')) {
            // Make comparison opposite of $not by prepending the value with a ! sign
            $value = substr($value, 1);
            $not   = true;
        }

        if (strtoupper(substr((string) $value, -4, 4)) === 'NULL') {
            $value = null;
        }

        if ($value === null) {
            $null = $not;
        }

        if (isset($null)) {
            // We have to do a NULL comparison
            return ' ' . $column . ' IS ' . ($null ? 'NOT ' : '') . 'NULL ';
        }

        // Add the label
        $execute[$label] = $value;

        if ($not) {
            // (My)Sql curiosity: When comparing != string, NULL values are NOT evaluated
            return ' (' . $column . ' != ' . Strings::ensureBeginsWith($label, ':') . ' OR ' . $column . ' IS NULL)';
        }

        return ' ' . $column . ' = ' . Strings::ensureBeginsWith($label, ':');
    }


    /**
     * Return a valid " LIMIT X, Y " string built from the specified parameters
     *
     * @param int|null $limit
     * @param int|null $page
     *
     * @return string The SQL " LIMIT X, Y " string
     */
    public static function getQueryStringLimit(?int $limit = null, ?int $page = null): string
    {
        $limit = Paging::getLimit($limit);

        if (!$limit) {
            // No limits, so show all
            return '';
        }

        return ' LIMIT ' . ((Paging::getPage($page) - 1) * $limit) . ', ' . $limit;
    }


    /**
     * Show the specified SQL query in a debug
     *
     * @param string|PDOStatement $query
     * @param ?array              $execute
     * @param bool                $return_only
     *
     * @return mixed
     * @throws SqlException
     */
    public static function show(string|PDOStatement $query, ?array $execute = null, bool $return_only = false): mixed
    {
        $query = static::renderQueryString($query, $execute, true);

        if ($return_only) {
            return $query;
        }

        if (!Core::readRegister('debug', 'clean')) {
            $query = str_replace("\n", ' ', $query);
            $query = Strings::replaceDouble($query, ' ', '\s');
        }

        // Debug::enabled() already logs the query, do not log it again
        if (!Debug::isEnabled()) {
            Log::debug(Strings::ensureEndsWith($query, ';'));
        }

        return Debug::show(Strings::ensureEndsWith($query, ';'), trace_offset: 6);
    }


    /**
     * Builds and returns a query string from the specified query and execute parameters
     *
     * @param string|PDOStatement $query
     * @param array|null          $execute
     * @param bool                $clean
     *
     * @return string
     */
    public static function renderQueryString(string|PDOStatement $query, ?array $execute = null, bool $clean = false): string
    {
        if (is_object($query)) {
            if (!($query instanceof PDOStatement)) {
                throw new SqlException(tr('Object of unknown class ":class" specified where PDOStatement was expected', [':class' => get_class($query)]));
            }
            // Query to be logged is a PDO statement, extract the query
            $query = $query->queryString;
        }

        $query = trim($query);

        if ($clean) {
            // Remove whitespace and comments
            $query = preg_replace('/#.+\n/', '', $query);
            $query = preg_replace('/--.+\n/', '', $query);
            $query = Strings::cleanWhiteSpace($query);
        }

        // Apply execution variables
        if (is_array($execute)) {
            /*
             * Reverse key sort to ensure that there are keys that contain at least parts of other keys will not be used
             * incorrectly
             *
             * example:
             *
             * array(category    => test,
             *       category_id => 5)
             *
             * Would cause the query to look like `category` = "test", `category_id` = "test"_id
             */
            krsort($execute);

            foreach ($execute as $key => $value) {
                if (is_string($value)) {
                    $value = addslashes($value);
                    $query = str_replace($key, '"' . Strings::Log($value) . '"', $query);

                } elseif (is_null($value)) {
                    $query = str_replace($key, ' ' . tr('NULL') . ' ', $query);

                } elseif (is_bool($value)) {
                    $query = str_replace($key, Strings::fromBoolean($value), $query);

                } else {
                    if (!is_scalar($value)) {
                        throw new LogException(tr('Query ":query" $execute key ":key" has non-scalar value ":value"', [
                            ':key'   => $key,
                            ':value' => $value,
                            ':query' => $query,
                        ]));
                    }

                    $query = str_replace((string) $key, (string) $value, $query);
                }
            }
        }

        return $query;
    }


    /**
     * Ensure that the specified query is either a select query or a show query
     *
     * @param string|PDOStatement $query
     * @param array|null          $execute
     *
     * @return void
     */
    public static function checkShowSelect(string|PDOStatement $query, ?array $execute): void
    {
        if (is_object($query)) {
            $query = $query->queryString;
        }

        $query = strtolower(substr(trim($query), 0, 10));

        if (!str_starts_with($query, 'select') and !str_starts_with($query, 'show')) {
            throw new SqlException(tr('Query ":query" is not a SELECT or SHOW query and as such cannot return results', [
                ':query' => Strings::log(static::getLogPrefix() . Log::sql($query, $execute), 4096),
            ]));
        }
    }


    /**
     * Helper for building $this->in key value pairs
     *
     * @param array           $in
     * @param string|int|null $column_starts_with
     *
     * @return string a comma delimited string of columns
     * @package   sql
     *
     * @copyright Copyright © 2022 Sven Olaf Oostenbrink
     * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
         */
    public static function inColumns(array $in, string|int|null $column_starts_with = null): string
    {
        if ($column_starts_with) {
            // Only return those columns that start with this string
            foreach ($in as $key => $column) {
                if (!Strings::ensureBeginsWith($key, $column_starts_with)) {
                    unset($in[$key]);
                }
            }
        }

        return implode(', ', array_keys($in));
    }


    /**
     * Check if this query is a write query and if the system allows writes
     *
     * @param string|PDOStatement $query
     *
     * @return void
     */
    public static function checkWriteAllowed(string|PDOStatement $query): void
    {
        $query = trim($query);
        $query = substr(trim($query), 0, 10);
        $query = strtolower($query);

        if (str_starts_with($query, 'insert') or str_starts_with($query, 'update')) {
            // This is a write query, check if we are not in readonly mode
            Core::checkReadonly('write query');
        }
    }
}
