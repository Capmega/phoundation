<?php

/**
 * Class SqlQueries
 *
 * This class contains a variety of methods used to build and manage queries
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Databases\Sql;

use PDOStatement;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Exception\LogException;
use Phoundation\Core\Log\Log;
use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Developer\Debug;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;


class SqlQueries
{
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
     * @param array       $source
     * @param string|null $prefix
     * @param bool        $insert
     * @param array|null  $skip
     *
     * @return array
     */
    public static function getBoundValues(array $source, ?string $prefix = null, bool $insert = false, ?array $skip = null): array
    {
        $return = [];

        foreach ($source as $key => $value) {
            if (($key === 'meta_id') and !$insert) {
                // Only process meta_id on insert operations
                continue;
            }

            if ($skip and in_array($key, $skip)) {
                // Don't make a bound variable for this one
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

        $label  = Strings::ensureStartsWith($label ?? $column, ':');
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

                if (($value === null) or (strtoupper(substr((string) $value, -4, 4)) === 'NULL')) {
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
                $return[] = ' ' . $column . ' IN (' . implode(', ', array_keys($in)) . ')';
            }

            if ($notin) {
                $notin   = static::in($notin, start: count($execute));
                $execute = array_merge((array) $execute, $notin);

                if (!isset($null)) {
                    // (My)Sql curiosity: When comparing != string, NULL values are NOT evaluated
                    $return[] = ' (' . $column . ' NOT IN (' . implode(', ', array_keys($notin)) . ') OR ' . $column . ' IS NULL)';
                } else {
                    $return[] = ' ' . $column . ' NOT IN (' . implode(', ', array_keys($notin)) . ')';
                }
            }

            if (isset($null)) {
                $return[] = static::isSingle($column, $null, $label, $execute);
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

        $column = Strings::ensureStartsWith($column, ':');
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
            return ' (' . $column . ' != ' . Strings::ensureStartsWith($label, ':') . ' OR ' . $column . ' IS NULL)';
        }

        return ' ' . $column . ' = ' . Strings::ensureStartsWith($label, ':');
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

        // Debug::enabled() already logs the query, don't log it again
        if (!Debug::isEnabled()) {
            Log::debug(static::getLogPrefix() . Strings::ensureEndsWith($query, ';'));
        }

        return Debug::show(Strings::ensureEndsWith($query, ';'), 6);
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
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
         */
    public static function inColumns(array $in, string|int|null $column_starts_with = null): string
    {
        if ($column_starts_with) {
            // Only return those columns that start with this string
            foreach ($in as $key => $column) {
                if (!Strings::ensureStartsWith($key, $column_starts_with)) {
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
            // This is a write query, check if we're not in readonly mode
            Core::checkReadonly('write query');
        }
    }
}
