<?php

declare(strict_types=1);
namespace Phoundation\Databases;

use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Databases\Sql\Sql;
use Phoundation\Databases\Sql\SqlQueries;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;


/**
 * SqlSimple class
 *
 * This class is the Simple SQL database access class, which allows to easily execute queries
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
exit('Killed by Databases\Sql\Simple');
class SqlSimple
{
    /**
     * @var Sql|null $sql
     */
    protected ?Sql $sql = null;


    /**
     * SimpleSql constructor.
     *
     * @param Sql $sql
     */
    public function __construct(Sql $sql)
    {
        $this->sql = $sql;
    }


    /**
     * Build and execut a SQL function that lists entries from the specified table using the specified parameters
     *
     * This function can build a SELECT query, specifying the required table columns, WHERE filtering, ORDER BY, and LIMIT
     *
     * @see Simple::get()
     * @note Any filter key that has the value "-" WILL BE IGNORED
     * @version 2.5.38: Added function and documentation
     *
     * @param array $params The parameters for the SELECT command
     * @param enum(resource, array) $params[method]
     * @param string $params[connector]
     * @param string $params[table]
     * @param list $params[columns]
     * @param null array $params[filters]
     * @param null array $params[orderby]
     * @param null list $params[joins]
     * @param false boolean $params[debug]
     * @param null boolean $params[auto_status]
     * @return mixed The entries from the requested table
     */
    public function list($params)
    {
        Arrays::ensure($params, 'joins,debug,limit,page,combine');

        if (empty($params['table'])) {
            throw new SqlException(tr('No table specified'));
        }

        if (empty($params['columns'])) {
            throw new SqlException(tr('No columns specified'));
        }

        Arrays::default($params, 'connector', null);
        Arrays::default($params, 'method', 'resource');
        Arrays::default($params, 'filters', array('status' => null));
        Arrays::default($params, 'orderby', null);
        Arrays::default($params, 'auto_status', null);

        /*
         * Apply automatic filter settings
         */
        if (($params['auto_status'] !== false) and !array_key_exists('status', $params['filters']) and !array_key_exists($params['table'] . '.status', $params['filters'])) {
            /*
             * Automatically ensure we only get entries with the auto status
             */
            $params['filters']['&' . $params['table'] . '.status'] = $params['auto_status'];
        }

        $columns = Sql::getColumnsString($params['columns'], $params['table']);
        $joins = Strings::force($params['joins'], ' ');
        $where = Sql::getWhereString($params['filters'], $execute, $params['table'], $params['combine']);
        $orderby = Sql::getOrderbyString($params['orderby']);
        $limit = Sql::limit($params['limit'], $params['page']);
        $resource = Sql::query(($params['debug'] ? ' ' : '') . 'SELECT ' . $columns . ' FROM  `' . $params['table'] . '` ' . $joins . $where . $orderby . $limit, $execute, $params['connector']);

        /*
         * Execute query and return results
         */
        switch ($params['method']) {
            case 'resource':
                /*
                 * Return a query instead of a list array
                 */
                return $resource;

            case 'array':
                /*
                 * Return a list array instead of a query
                 */
                return Sql::list($resource);

            default:
                throw new SqlException(tr('Sql::simple_list(): Unknown method ":method" specified', array(':method' => $method)), 'unknown');
        }
    }


    /**
     * Build and execut a SQL function that returns a single entry from the specified table using the specified parameters
     *
     * This function can build a SELECT query, specifying the required table columns, WHERE filtering
     *
     * @see Simple::list()
     * @note Any filter key that has the value "-" WILL BE IGNORED
     * @version 2.5.38: Added function and documentation
     *
     * @param array $params A parameters array
     * @param enum(resource, array) $params[method]
     * @param string $params[connector]
     * @param string $params[table]
     * @param list $params[columns]
     * @param null array $params[filters]
     * @param null array $params[orderby]
     * @param null list $params[joins]
     * @param false boolean $params[debug]
     * @param null boolean $params[auto_status]
     * @return mixed The entries from the requested table
     */
    public function get($params)
    {
        Arrays::ensure($params, 'joins,debug,combine');

        if (empty($params['table'])) {
            throw new SqlException(tr('No table specified'));
        }

        if (empty($params['columns'])) {
            throw new SqlException(tr('No columns specified'));
        }

        Arrays::default($params, 'connector', null);
        Arrays::default($params, 'single', null);
        Arrays::default($params, 'filters', array('status' => null));
        Arrays::default($params, 'auto_status', null);
        Arrays::default($params, 'page', null);
        Arrays::default($params, 'template', false);

        $params['columns'] = Arrays::force($params['columns']);

        /*
         * Apply automatic filter settings
         */
        if (($params['auto_status'] !== false) and !array_key_exists('status', $params['filters']) and !array_key_exists($params['table'] . '.status', $params['filters'])) {
            /*
             * Automatically ensure we only get entries with the auto status
             */
            $params['filters']['&' . $params['table'] . '.status'] = $params['auto_status'];
        }

        if ((count($params['columns']) === 1) and ($params['single'] !== false)) {
            /*
             * By default, when one column is selected, return the value
             * directly, instead of in an array
             */
            $params['single'] = true;
        }

        $columns = Sql::getColumnsString($params['columns'], $params['table']);
        $joins = Strings::force($params['joins'], ' ');
        $where = Sql::getWhereString($params['filters'], $execute, $params['table'], $params['combine']);
        $return = Sql::get(($params['debug'] ? ' ' : '') . 'SELECT ' . $columns . ' FROM  `' . $params['table'] . '` ' . $joins . $where, $execute, $params['single'], $params['connector']);

        if ($return) {
            return $return;
        }

        if ($params['template']) {
            /*
             * Return a "template" result
             */
            $return = array();

            foreach ($params['columns'] as $column) {
                $return[$column] = null;
            }
        }

        return $return;
    }


    /**
     *
     *
     * @param String|null $value
     * @param bool $not
     * @return string
     */
    protected static function whereNull(?string $value, bool $not = false): string
    {
        if ($value === null) {
            if ($not) {
                return ' IS NOT NULL ';
            }

            return ' IS NULL ';
        }

        if ($not) {
            return ' != ' . Strings::quote($value);
        }

        return ' = ' . Strings::quote($value);
    }


    /**
     * Return a valid " WHERE `column` = :value ", " WHERE `column` IS NULL ", or " WHERE `column` IN (:values) " string built from the specified parameters
     *
     * @version 2.4.8: Added function and documentation
     * @param string $column
     * @param null|string|array $values
     * @param boolean $not
     * @param string|null $extra
     * @return string The SQL " WHERE.... " string
     */
    protected static function simpleWhere(string $column, null|string|array $values, bool $not = false, string|null $extra = null): string
    {
        $table = Strings::until($column, '.', 0, 0, true);
        $column = Strings::from($column, '.');

        if (!$values) {
            exit('Simple::simpleWhere() TEST THIS WHERE VALUES IS NULL');
            return $extra;
        }

        if (is_scalar($values)) {
            if ($not) {
                return ' WHERE ' . ($table ? '`' . $table . '`.' : '') . '`' . $column . '` != :' . $column . ' ' . $extra . ' ';
            }

            return ' WHERE ' . ($table ? '`' . $table . '`.' : '') . '`' . $column . '` = :' . $column . ' ' . $extra . ' ';
        }

        $not = ($not ? 'NOT' : '');

        if (($values === null) or ($values === 'null') or ($values === 'NULL')) {
            return ' WHERE ' . ($table ? '`' . $table . '`.' : '') . '`' . $column . '` IS ' . $not . ' NULL ' . $extra . ' ';
        }

        if (is_array($values)) {
            $values = SqlQueries::in($values);

            foreach ($values as $key => $value) {
                if (($value === null) or ($value === 'null') or ($value === 'NULL')) {
                    unset($values[$key]);
                    $extra = ' OR ' . ($table ? '`' . $table . '`.' : '') . '`' . $column . '` IS ' . $not . ' NULL ';
                    break;
                }
            }

            return ' WHERE (' . ($table ? '`' . $table . '`.' : '') . '`' . $column . '` ' . $not . ' IN (' . SqlQueries::inColumns($values) . ')' . $extra . ') ' . $extra . ' ';
        }

        throw new SqlException(tr('Specified values ":values" is neither NULL nor scalar nor an array', [':values' => $values]));
    }


    /**
     * Return a valid PDO execute array
     *
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     * @version 2.4.8: Added function and documentation
     *
     * @param string $column
     * @param string|array $values
     * @param array|null $extra
     * @return array The $execute array corrected
     */
    protected static function simpleExecute(string $column, string|array $values, ?array $extra = null): array
    {
        if (!$values) {
            return $extra;
        }

        if (is_scalar($values) or ($values === null)) {
            $values = array(Strings::startsWith($column, ':') => $values);

        } elseif (is_array($values)) {
            $values = SqlQueries::in($values, ':value', true, true);

        } else {
            throw new SqlException(tr('Specified values ":values" is neither NULL nor scalar nor an array', [
                ':values' => $values
            ]));
        }

        if ($extra) {
            $values = array_merge($values, $extra);
        }

        return $values;
    }


    /*
     * OBSOLETE / COMPATIBILITY FUNCTIONS
     *
     * These functions below exist only for compatibility between pdo.php and mysqli.php
     *
     * Return affected rows
     */


    /**
     * Build an SQL WHERE string out of the specified filters, typically used for basic foobar_list() like functions
     *
     * @note Any filter key that has the value "-" WILL BE IGNORED
     * @note Any keys prefixed with ! will perform a NOT operation
     * @note Any keys prefixed with ~ will perform a LIKE operation
     * @note Any keys prefixed with # will allow $value to be an arran and operate an IN operation
     * @note Key prefixes may be combined in any order
     * @version 2.5.38: Added function and documentation
     *
     * @param array $filters A key => value array with required filters
     * @param array $execute The $execute array that will be created by this function
     * @param string $table The table for which these colums will be setup
     * @param string|null $combine
     * @return string The WHERE string
     */
    protected static function getWhereString(array $filters, array &$execute, string $table, ?string $combine = null): string
    {
        $where = '';

        if (!$combine) {
            $combine = 'AND';
        }

        /*
         * Build the where section from the specified filters
         */
        foreach ($filters as $key => $value) {
            /*
             * Any entry with value BOOLEAN FALSE will not be considered. this
             * way we have a simple way to skip keys if needed
             */
            // :TODO: Look up why '-' also was considered "skip"
            if (($value === '-') or ($value === false)) {
                /*
                 * Ignore this entry
                 */
                continue;
            }

            $use_value = true;
            $like = false;
            $array = false;
            $not_string = '';
            $not = '';
            $use_combine = $combine;
            $comparison = '=';

            /*
             * Check for modifiers in the keys
             * ! will make it a NOT filter
             * # will allow arrays
             */
            while (true) {
                switch ($key[0]) {
                    case '*':
                        // Do not use value, key only
                        $key = substr($key, 1);
                        $use_value = false;
                        break;

                    case '<':
                        // Smaller than
                        if ($not) {
                            $comparison = '>=';

                        } else {
                            $comparison = '<';
                        }

                        $key = substr($key, 1);
                        break;

                    case '>':
                        // larger than
                        if ($not) {
                            $comparison = '<=';

                        } else {
                            $comparison = '>';
                        }

                        $key = substr($key, 1);
                        break;

                    case '&':
                        $key = substr($key, 1);
                        $use_combine = 'AND';
                        break;

                    case '|':
                        $key = substr($key, 1);
                        $use_combine = 'OR';
                        break;

                    case '~':
                        // LIKE
                        $key = substr($key, 1);
                        $like = true;
                        $value = '%' . $value . '%';
                        break;

                    case '!':
                        // NOT
                        $key = substr($key, 1);

                        switch ($comparison) {
                            case '<':
                                $comparison = '>=';
                                $not        = '';
                                break;

                            case '>':
                                $comparison = '<=';
                                $not        = '';
                                break;

                            default:
                                $not_string = ' NOT ';
                                $not        = '!';
                        }
                        break;

                    case '#':
                        // IN
                        $key   = substr($key, 1);
                        $array = true;

                    default:
                        break 2;
                }
            }

            if ($use_value) {
                if (!str_contains($key, '.')) {
                    $key = $table . '.' . $key;
                }

                $column = '`' . str_replace('.', '`.`', trim($key)) . '`';
                $key    = str_replace('.', '_', $key);

            } else {
                $column = trim($key);
            }

            if ($like) {
                if (!$use_value) {
                    throw new SqlException(tr('The specified filter key ":key" specified * to not use value, but also # to use LIKE which cannot work together', [
                        ':key' => $key
                    ]));
                }

                if (is_string($value)) {
                    $filter = ' ' . $column . ' ' . $not . 'LIKE :' . $key . ' ';
                    $execute[':' . $key] = $value;

                } else {
                    if (is_array($value)) {
                        throw new SqlException(tr('The specified filter key ":key" is an array, which is not allowed with a LIKE comparisson.', [
                            ':key' => $key
                        ]));
                    }

                    if (is_bool($value)) {
                        throw new SqlException(tr('The specified filter key ":key" is a boolean, which is not allowed with a LIKE comparisson.', [
                            ':key' => $key
                        ]));
                    }

                    if ($value === null) {
                        throw new SqlException(tr('The specified filter key ":key" is a null, which is not allowed with a LIKE comparisson.', [
                            ':key' => $key
                        ]));
                    }

                    throw new SqlException(tr('Specified value ":value" is of invalid datatype ":datatype"', [
                        ':value'    => $value,
                        ':datatype' => gettype($value)
                    ]));
                }

            } else {
                if (is_array($value)) {
                    if (!$use_value) {
                        throw new SqlException(tr('The specified filter key ":key" specified * to not use value, but the value contains an array while "null" is required', [
                            ':key' => $key
                        ]));
                    }

                    if ($array) {
                        throw new SqlException(tr('The specified filter key ":key" contains an array, which is not allowed. Specify the key as "#:array" to allow arrays', [
                            ':key'   => $key,
                            ':array' => $key
                        ]));
                    }

                    // The $value may be specified as an empty array, which then will be ignored
                    if ($value) {
                        $value = SqlQueries::in($value);
                        $filter = ' ' . $column . ' ' . $not_string . 'IN (' . SqlQueries::inColumns($value) . ') ';
                        $execute = array_merge($execute, $value);
                    }

                } elseif (is_bool($value)) {
                    if (!$use_value) {
                        throw new SqlException(tr('The specified filter key ":key" specified * to not use value, but the value contains a boolean while "null" is required', [
                            ':key' => $key
                        ]));
                    }

                    $filter = ' ' . $column . ' ' . $not . '= :' . $key . ' ';
                    $execute[':' . $key] = (integer) $value;

                } elseif (is_string($value)) {
                    if (!$use_value) {
                        throw new SqlException(tr('The specified filter key ":key" specified * to not use value, but the value contains a string while "null" is required', [
                            ':key' => $key
                        ]));
                    }

                    $filter = ' ' . $column . ' ' . $not . $comparison . ' :' . $key . ' ';
                    $execute[':' . $key] = $value;

                } elseif (is_numeric($value)) {
                    if (!$use_value) {
                        throw new SqlException(tr('The specified filter key ":key" specified * to not use value, but the value contains a number while "false" is required', [
                            ':key' => $key
                        ]));
                    }

                    $filter = ' ' . $column . ' ' . $not . $comparison . ' :' . $key . ' ';
                    $execute[':' . $key] = $value;

                } elseif ($value === null) {
                    if (!$use_value) {
                        // Do NOT use a value, so also don't add an execute value
                        $filter = ' ' . $column . ' ';

                    } else {
                        $filter = ' ' . $column . ' IS' . $not_string . ' :' . $key . ' ';
                        $execute[':' . $key] = $value;
                    }

                } else {
                    throw new SqlException(tr('Specified value ":value" is of invalid datatype ":datatype"', [
                        ':value'    => $value,
                        ':datatype' => gettype($value)
                    ]));
                }
            }

            if ($where) {
                $where .= ' ' . $use_combine . ' ' . $filter;

            } else {
                $where = ' WHERE ' . $filter;
            }
        }

        return $where;
    }


    /**
     * Build the SQL columns list for the specified columns list, escaping all columns with backticks
     *
     * If the specified column is of the "column" format, it will be returned as "`column`". If its of the "table.column" format, it will be returned as "`table`.`column`"
     *
     * @version 2.5.38: Added function and documentation
     * @param array|string $columns The list of columns from which the query must be built
     * @param string $table The table for which these colums will be setup
     * @return string The columns with column quotes
     */
    protected static function getColumnsString(string|array $columns, string $table): string
    {
        // Validate the columns
        if (!$columns) {
            throw new SqlException(tr('No columns specified'));
        }

        $columns = Arrays::force($columns);

        foreach ($columns as $id => &$column) {
            if (!$column) {
                unset($columns[$id]);
                continue;
            }

            $column = strtolower(trim($column));

            if (!str_contains($column, '.')) {
                $column = $table . '.' . $column;
            }

            if (str_contains($column, ' as ')) {
                $target = trim(Strings::from($column, ' as '));
                $column = trim(Strings::until($column, ' as '));
                $column = '`' . str_replace('.', '`.`', trim($column)) . '`';
                $column .= ' AS `' . trim($target) . '`';

            } else {
                $column = '`' . str_replace('.', '`.`', trim($column)) . '`';
            }
        }

        $columns = implode(', ', $columns);

        unset($column);
        return $columns;
    }


    /**
     * Build the SQL columns list for the specified columns list
     *
     * @version 2.5.38: Added function and documentation
     * @param array $orderby A key => value array containing the columns => direction definitions
     * @return string The columns with column quotes
     */
    protected static function getOrderbyString(array $orderby): string
    {
        // Validate the columns
        if (!$orderby) {
            return '';
        }

        $return = [];

        foreach ($orderby as $column => $direction) {
            if (!is_string($direction)) {
                throw new SqlException(tr('Specified orderby direction ":direction" for column ":column" is invalid, it should be a string', [
                    ':direction' => $direction,
                    ':column'    => $column
                ]));
            }

            $direction = strtoupper($direction);

            switch ($direction) {
                case 'ASC':
                    // FALLTHOGUH
                case 'DESC':
                    break;

                default:
                    throw new SqlException(tr('Specified orderby direction ":direction" for column ":column" is invalid, it should be either "ASC" or "DESC"', [
                        ':direction' => $direction,
                        ':column'    => $column
                    ]));
            }

            $return[] = '`' . $column . '` ' . $direction;
        }

        $return = implode(', ', $return);

        return ' ORDER BY ' . $return . ' ';
    }
}
