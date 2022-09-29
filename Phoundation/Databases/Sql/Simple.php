<?php

namespace Phoundation\Databases;

use Debug;
use Exception;
use PDOStatement;
use Phoundation\Core\CoreException;
use Phoundation\Core\Json\Strings;
use Phoundation\Core\Log\Log;
use Phoundation\Databases\Exception\SqlException;

/**
 * SqlSimple class
 *
 * This class is the Simple SQL database access class, which allows to easily execute queries
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
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
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     * @see Sql::simple_get()
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
            throw new SqlException(tr('Sql::simple_list(): No table specified'), 'not-specified');
        }

        if (empty($params['columns'])) {
            throw new SqlException(tr('Sql::simple_list(): No columns specified'), 'not-specified');
        }

        array_default($params, 'connector', null);
        array_default($params, 'method', 'resource');
        array_default($params, 'filters', array('status' => null));
        array_default($params, 'orderby', null);
        array_default($params, 'auto_status', null);

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
        $joins = str_force($params['joins'], ' ');
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
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     * @see Sql::simple_list()
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
            throw new SqlException(tr('Sql::simple_get(): No table specified'), 'not-specified');
        }

        if (empty($params['columns'])) {
            throw new SqlException(tr('Sql::simple_get(): No columns specified'), 'not-specified');
        }

        array_default($params, 'connector', null);
        array_default($params, 'single', null);
        array_default($params, 'filters', array('status' => null));
        array_default($params, 'auto_status', null);
        array_default($params, 'page', null);
        array_default($params, 'template', false);

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
        $joins = str_force($params['joins'], ' ');
        $where = Sql::getWhereString($params['filters'], $execute, $params['table'], $params['combine']);
        $retval = Sql::get(($params['debug'] ? ' ' : '') . 'SELECT ' . $columns . ' FROM  `' . $params['table'] . '` ' . $joins . $where, $execute, $params['single'], $params['connector']);

        if ($retval) {
            return $retval;
        }

        if ($params['template']) {
            /*
             * Return a "template" result
             */
            $retval = array();

            foreach ($params['columns'] as $column) {
                $retval[$column] = null;
            }
        }

        return $retval;
    }



    /**
     *
     *
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param
     * @return
     */
    protected static function whereNull($value, $not = false)
    {
        try {
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

        } catch (SqlException $e) {
            throw new SqlException('Sql::whereNull(): Failed', $e);
        }
    }



    /**
     * Return a valid " WHERE `column` = :value ", " WHERE `column` IS NULL ", or " WHERE `column` IN (:values) " string built from the specified parameters
     *
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     * @version 2.4.8: Added function and documentation
     *
     * @param string $column
     * @param mixed $values
     * @param boolean $not
     * @return string The SQL " WHERE.... " string
     */
    protected static function simpleWhere(string $column, mixed $values, bool $not = false, $extra = null)
    {
        try {
            $table = Strings::until($column, '.', 0, 0, true);
            $column = Strings::from($column, '.');

            if (!$values) {
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
                $values = Sql::in($values);

                foreach ($values as $key => $value) {
                    if (($value === null) or ($value === 'null') or ($value === 'NULL')) {
                        unset($values[$key]);
                        $extra = ' OR ' . ($table ? '`' . $table . '`.' : '') . '`' . $column . '` IS ' . $not . ' NULL ';
                        break;
                    }
                }

                return ' WHERE (' . ($table ? '`' . $table . '`.' : '') . '`' . $column . '` ' . $not . ' IN (' . Sql::inColumns($values) . ')' . $extra . ') ' . $extra . ' ';
            }

            throw new SqlException(tr('Sql::simpleWhere(): Specified values ":values" is neither NULL nor scalar nor an array', array(':values' => $values)), 'invalid');

        } catch (Exception $e) {
            throw new SqlException(tr('Sql::simpleWhere(): Failed'), $e);
        }
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
     * @param mixed $values
     * @return params The execute array corrected
     */
    protected static function simpleExecute($column, $values, $extra = null)
    {
        try {
            if (!$values) {
                return $extra;
            }

            if (is_scalar($values) or ($values === null)) {
                $values = array(Strings::startsWith($column, ':') => $values);

            } elseif (is_array($values)) {
                $values = Sql::in($values, ':value', true, true);

            } else {
                throw new SqlException(tr('Sql::simpleExecute(): Specified values ":values" is neither NULL nor scalar nor an array', array(':values' => $values)), 'invalid');
            }

            if ($extra) {
                $values = array_merge($values, $extra);
            }

            return $values;

        } catch (Exception $e) {
            throw new SqlException(tr('Sql::simpleExecute(): Failed'), $e);
        }
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
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     * @note Any filter key that has the value "-" WILL BE IGNORED
     * @note Any keys prefixed with ! will perform a NOT operation
     * @note Any keys prefixed with ~ will perform a LIKE operation
     * @note Any keys prefixed with # will allow $value to be an arran and operate an IN operation
     * @note Key prefixes may be combined in any order
     * @version 2.5.38: Added function and documentation
     *
     * @param array A key => value array with required filters
     * @param byref array $execute The execute array that will be created by this function
     * @param string $table The table for which these colums will be setup
     * @return string The WHERE string
     */
    protected static function getWhereString($filters, &$execute, $table, $combine = null)
    {
        try {
            $where = '';

            if (!is_array($filters)) {
                throw new SqlException(tr('Sql::getWhereString(): The specified filters are invalid, it should be a key => value array'), 'invalid');
            }

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
                            /*
                             * Do not use value, key only
                             */
                            $key = substr($key, 1);
                            $use_value = false;
                            break;

                        case '<':
                            /*
                             * Smaller than
                             */
                            if ($not) {
                                $comparison = '>=';

                            } else {
                                $comparison = '<';
                            }

                            $key = substr($key, 1);
                            break;

                        case '>':
                            /*
                             * larger than
                             */
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
                            /*
                             * LIKE
                             */
                            $key = substr($key, 1);
                            $like = true;
                            $value = '%' . $value . '%';
                            break;

                        case '!':
                            /*
                             * NOT
                             */
                            $key = substr($key, 1);

                            switch ($comparison) {
                                case '<':
                                    $comparison = '>=';
                                    $not = '';
                                    break;

                                case '>':
                                    $comparison = '<=';
                                    $not = '';
                                    break;

                                default:
                                    $not_string = ' NOT ';
                                    $not = '!';
                            }
                            break;

                        case '#':
                            /*
                             * IN
                             */
                            $key = substr($key, 1);
                            $array = true;

                        default:
                            break 2;
                    }
                }

                if ($use_value) {
                    if (strpos($key, '.') === false) {
                        $key = $table . '.' . $key;
                    }

                    $column = '`' . str_replace('.', '`.`', trim($key)) . '`';
                    $key = str_replace('.', '_', $key);

                } else {
                    $column = trim($key);
                }

                if ($like) {
                    if (!$use_value) {
                        throw new SqlException(tr('Sql::getWhereString(): The specified filter key ":key" specified * to not use value, but also # to use LIKE which cannot work together', array(':key' => $key)), 'invalid');
                    }

                    if (is_string($value)) {
                        $filter = ' ' . $column . ' ' . $not . 'LIKE :' . $key . ' ';
                        $execute[':' . $key] = $value;

                    } else {
                        if (is_array($value)) {
                            throw new SqlException(tr('Sql::getWhereString(): The specified filter key ":key" is an array, which is not allowed with a LIKE comparisson.', array(':key' => $key)), 'invalid');
                        }

                        if (is_bool($value)) {
                            throw new SqlException(tr('Sql::getWhereString(): The specified filter key ":key" is a boolean, which is not allowed with a LIKE comparisson.', array(':key' => $key)), 'invalid');
                        }

                        if ($value === null) {
                            throw new SqlException(tr('Sql::getWhereString(): The specified filter key ":key" is a null, which is not allowed with a LIKE comparisson.', array(':key' => $key)), 'invalid');
                        }

                        throw new SqlException(tr('Sql::getWhereString(): Specified value ":value" is of invalid datatype ":datatype"', array(':value' => $value, ':datatype' => gettype($value))), 'invalid');
                    }

                } else {
                    if (is_array($value)) {
                        if (!$use_value) {
                            throw new SqlException(tr('Sql::getWhereString(): The specified filter key ":key" specified * to not use value, but the value contains an array while "null" is required', array(':key' => $key)), 'invalid');
                        }

                        if ($array) {
                            throw new SqlException(tr('Sql::getWhereString(): The specified filter key ":key" contains an array, which is not allowed. Specify the key as "#:array" to allow arrays', array(':key' => $key, ':array' => $key)), 'invalid');
                        }

                        /*
                         * The $value may be specified as an empty array, which then
                         * will be ignored
                         */
                        if ($value) {
                            $value = Sql::in($value);
                            $filter = ' ' . $column . ' ' . $not_string . 'IN (' . Sql::in_columns($value) . ') ';
                            $execute = array_merge($execute, $value);
                        }

                    } elseif (is_bool($value)) {
                        if (!$use_value) {
                            throw new SqlException(tr('Sql::getWhereString(): The specified filter key ":key" specified * to not use value, but the value contains a boolean while "null" is required', array(':key' => $key)), 'invalid');
                        }

                        $filter = ' ' . $column . ' ' . $not . '= :' . $key . ' ';
                        $execute[':' . $key] = (integer)$value;

                    } elseif (is_string($value)) {
                        if (!$use_value) {
                            throw new SqlException(tr('Sql::getWhereString(): The specified filter key ":key" specified * to not use value, but the value contains a string while "null" is required', array(':key' => $key)), 'invalid');
                        }

                        $filter = ' ' . $column . ' ' . $not . $comparison . ' :' . $key . ' ';
                        $execute[':' . $key] = $value;

                    } elseif (is_numeric($value)) {
                        if (!$use_value) {
                            throw new SqlException(tr('Sql::getWhereString(): The specified filter key ":key" specified * to not use value, but the value contains a number while "false" is required', array(':key' => $key)), 'invalid');
                        }

                        $filter = ' ' . $column . ' ' . $not . $comparison . ' :' . $key . ' ';
                        $execute[':' . $key] = $value;

                    } elseif ($value === null) {
                        if (!$use_value) {
                            /*
                             * Do NOT use a value, so also don't add an execute
                             * value
                             */
                            $filter = ' ' . $column . ' ';

                        } else {
                            $filter = ' ' . $column . ' IS' . $not_string . ' :' . $key . ' ';
                            $execute[':' . $key] = $value;
                        }

                    } else {
                        throw new SqlException(tr('Sql::getWhereString(): Specified value ":value" is of invalid datatype ":datatype"', array(':value' => $value, ':datatype' => gettype($value))), 'invalid');
                    }
                }

                if ($where) {
                    $where .= ' ' . $use_combine . ' ' . $filter;

                } else {
                    $where = ' WHERE ' . $filter;
                }
            }

            return $where;

        } catch (Exception $e) {
            throw new SqlException('Sql::getWhereString(): Failed', $e);
        }
    }



    /**
     * Build the SQL columns list for the specified columns list, escaping all columns with backticks
     *
     * If the specified column is of the "column" format, it will be returned as "`column`". If its of the "table.column" format, it will be returned as "`table`.`column`"
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     * @version 2.5.38: Added function and documentation
     *
     * @param csv array $columns The list of columns from which the query must be built
     * @param string $table The table for which these colums will be setup
     * @return string The columns with column quotes
     */
    protected static function getColumnsString($columns, $table)
    {
        try {
            /*
             * Validate the columns
             */
            if (!$columns) {
                throw new SqlException(tr('Sql::getColumnsString(): No columns specified'));
            }

            $columns = Arrays::force($columns);

            foreach ($columns as $id => &$column) {
                if (!$column) {
                    unset($columns[$id]);
                    continue;
                }

                $column = strtolower(trim($column));

                if (strpos($column, '.') === false) {
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

        } catch (Exception $e) {
            throw new SqlException('Sql::getColumnsString(): Failed', $e);
        }
    }



    /**
     * Build the SQL columns list for the specified columns list
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     * @version 2.5.38: Added function and documentation
     *
     * @param array $orderby A key => value array containing the columns => direction definitions
     * @return string The columns with column quotes
     */
    protected static function getOrderbyString($orderby)
    {
        try {
            /*
             * Validate the columns
             */
            if (!$orderby) {
                return '';
            }

            if (!is_array($orderby)) {
                throw new SqlException(tr('Sql::getOrderbyString(): Specified orderby ":orderby" should be an array but is a ":datatype"', array(':orderby' => $orderby, ':datatype' => gettype($orderby))), 'invalid');
            }

            foreach ($orderby as $column => $direction) {
                if (!is_string($direction)) {
                    throw new SqlException(tr('Sql::getOrderbyString(): Specified orderby direction ":direction" for column ":column" is invalid, it should be a string', array(':direction' => $direction, ':column' => $column)), 'invalid');
                }

                $direction = strtoupper($direction);

                switch ($direction) {
                    case 'ASC':
                        // FALLTHOGUH
                    case 'DESC':
                        break;

                    default:
                        throw new SqlException(tr('Sql::getOrderbyString(): Specified orderby direction ":direction" for column ":column" is invalid, it should be either "ASC" or "DESC"', array(':direction' => $direction, ':column' => $column)), 'invalid');
                }

                $retval[] = '`' . $column . '` ' . $direction;
            }

            $retval = implode(', ', $retval);

            return ' ORDER BY ' . $retval . ' ';

        } catch (Exception $e) {
            throw new SqlException('Sql::getOrderbyString(): Failed', $e);
        }
    }
}