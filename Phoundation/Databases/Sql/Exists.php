<?php

namespace Phoundation\Databases;

/**
 * SQL Exists library
 *
 * This library contains various "exists" functions to check if a database, table, column, etc exists, or not
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation/Databases
 */
class SqlExists
{
    /**
     * return if database exists
     *
     * @author Sven Oostenbrink <support@capmega.com>
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @category Function reference
     * @package sql-exists
     *
     * @param string $database The dtaabase to be tested
     * @param null string $query The query to be executed if the database exists. If the query starts with an exclamation mark (!), the query will be executed if the database does NOT exist
     * @return boolean True if the specified index exists, false otherwise
     */
    public static function databaseExists($database, $query = null, $connector = null) {
        try {
            $return = sql_query('SHOW DATABASES LIKE "'.cfm($database).'"', null, $connector);

            if (substr($query, 0, 1) == '!') {
                $not   = true;
                $query = substr($query, 1);

            } else {
                $not = false;
            }

            if (empty($return) xor $not) {
                return false;
            }

            if ($query) {
                sql_query($query, null, $connector);
            }

            return array_shift($return);

        }catch(Exception $e) {
            throw new OutOfBoundsException('sql_database_exists(): Failed', $e);
        }
    }



    /**
     * Returns if specified column exists
     *
     * If query is specified, the query will be executed only if the specified function exists
     * If the query is prefixed with an exclamation mark ! then the query will only be executed if the function does NOT exist
     *
     * @author Infospace <infospace@capmega.com>
     * @author Sven Oostenbrink <support@capmega.com>
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @category Function reference
     * @package sql-exists
     *
     * @param string $table The table which should be checked for
     * @param null string $query The query to be executed if the database exists. If the query starts with an exclamation mark (!), the query will be executed if the database does NOT exist
     * @param null mixed $connector If specified, executed this function on the specified database connector. If not specified, use the current database connector
     * @return boolean True if the specified index exists, false otherwise
     */
    public static function tableExists($table, $query = null, $connector = null) {
        global $pdo;

        try {
            $return = sql_list('SHOW TABLES LIKE "'.cfm($table).'"', null, null, $connector);

            if (substr($query, 0, 1) == '!') {
                $not   = true;
                $query = substr($query, 1);

            } else {
                $not = false;
            }

            if (empty($return) xor $not) {
                return false;
            }

            if ($query) {
                sql_query($query, null, $connector);
            }

            return $return;

        }catch(Exception $e) {
            throw new OutOfBoundsException('sql_table_exists(): Failed', $e);
        }
    }



    /*
     * Returns if specified index exists
     *
     * If query is specified, the query will be executed only if the specified function exists
     * If the query is prefixed with an exclamation mark ! then the query will only be executed if the function does NOT exist
     *
     * @author Sven Oostenbrink <support@capmega.com>
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @category Function reference
     * @package sql-exists
     *
     * @param string $table The table on which the index should be found
     * @param string $index The index to be tested
     * @param null string $query The query to be executed if the database exists. If the query starts with an exclamation mark (!), the query will be executed if the database does NOT exist
     * @param null mixed $connector If specified, executed this function on the specified database connector. If not specified, use the current database connector
     * @return boolean True if the specified index exists, false otherwise
     */
    public static function indexExists($table, $index, $query = null, $connector = null) {
        global $pdo;

        try {
            $return = sql_list('SHOW INDEX FROM `'.cfm($table).'` WHERE `Key_name` = "'.cfm($index).'"', null, null, $connector);

            if (substr($query, 0, 1) == '!') {
                $not   = true;
                $query = substr($query, 1);

            } else {
                $not = false;
            }

            if (empty($return) xor $not) {
                return false;
            }

            if ($query) {
                sql_query($query, null, $connector);
            }

            return array_shift($return);

        }catch(Exception $e) {
            throw new OutOfBoundsException('sql_index_exists(): Failed', $e);
        }
    }



    /*
     * Returns if specified column exists
     *
     * If query is specified, the query will be executed only if the specified function exists
     * If the query is prefixed with an exclamation mark ! then the query will only be executed if the function does NOT exist
     *
     * @author Sven Oostenbrink <support@capmega.com>
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @category Function reference
     * @package sql-exists
     *
     * @param string $table The table on which the foreign keys should be found
     * @param string $column The column on which the foreign keys should be found
     * @param null string $query The query to be executed if the database exists. If the query starts with an exclamation mark (!), the query will be executed if the database does NOT exist
     * @param null mixed $connector If specified, executed this function on the specified database connector. If not specified, use the current database connector
     * @return boolean True if the specified index exists, false otherwise
     */
    public static function columnExists($table, $column, $query = null, $connector = null) {
        global $pdo;

        try {
            $return = sql_get('SHOW COLUMNS FROM `'.cfm($table).'` WHERE `Field` = "'.cfm($column).'"', null, null, $connector);

            if (substr($query, 0, 1) == '!') {
                $not   = true;
                $query = substr($query, 1);

            } else {
                $not = false;
            }

            if (empty($return) xor $not) {
                return false;
            }

            if ($query) {
                sql_query($query, null, $connector);
            }

            return $return;

        }catch(Exception $e) {
            throw new OutOfBoundsException('sql_column_exists(): Failed', $e);
        }
    }



    /*
     * Returns if specified foreign key exists
     *
     * If query is specified, the query will be executed only if the specified function exists
     * If the query is prefixed with an exclamation mark ! then the query will only be executed if the function does NOT exist
     *
     * @author Sven Oostenbrink <support@capmega.com>
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @category Function reference
     * @package sql-exists
     *
     * @param string $table The table on which the index should be found
     * @param string $index The index to be tested
     * @param null string $query The query to be executed if the database exists. If the query starts with an exclamation mark (!), the query will be executed if the database does NOT exist
     * @param null mixed $connector If specified, executed this function on the specified database connector. If not specified, use the current database connector
     * @return boolean True if the specified index exists, false otherwise
     */
    public static function foreignKeyExists($table, $foreign_key, $query = null, $connector = null) {
        global $pdo, $_CONFIG;

        try {
            $connector = sql_connector_name($connector);
            $database  = $_CONFIG['db'][$connector]['db'];

            $return    = sql_get('SELECT *

                              FROM   `information_schema`.`TABLE_CONSTRAINTS`

                              WHERE  `CONSTRAINT_TYPE`   = "FOREIGN KEY"
                              AND    `CONSTRAINT_SCHEMA` = "'.cfm($database).'"
                              AND    `TABLE_NAME`        = "'.cfm($table).'"
                              AND    `CONSTRAINT_NAME`   = "'.cfm($foreign_key).'"', null, null, $connector);

            if (substr($query, 0, 1) == '!') {
                $not   = true;
                $query = substr($query, 1);

            } else {
                $not = false;
            }

            if (empty($return) xor $not) {
                return false;
            }

            if ($query) {
                sql_query($query, null, $connector);
            }

            return $return;

        }catch(Exception $e) {
            throw new OutOfBoundsException('sql_foreignkey_exists(): Failed', $e);
        }
    }



    /*
     * Returns if specified function exists
     *
     * If query is specified, the query will be executed only if the specified function exists
     * If the query is prefixed with an exclamation mark ! then the query will only be executed if the function does NOT exist
     *
     * @author Sven Oostenbrink <support@capmega.com>
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @category Function reference
     * @package sql-exists
     *
     * @param string $table The table on which the index should be found
     * @param string $index The index to be tested
     * @param null string $query The query to be executed if the database exists. If the query starts with an exclamation mark (!), the query will be executed if the database does NOT exist
     * @param null mixed $connector If specified, executed this function on the specified database connector. If not specified, use the current database connector
     * @return boolean True if the specified index exists, false otherwise
     */
    public static function functionExists($name, $query = null, $database = null, $connector = null) {
        global $pdo, $_CONFIG;

        try {
            $connector = sql_connector_name($connector);

            if (!$database) {
                $database = $_CONFIG['db'][$connector]['db'];
            }

            $return = sql_get('SELECT `ROUTINE_NAME`

                           FROM   `INFORMATION_SCHEMA`.`ROUTINES`

                           WHERE  `ROUTINE_SCHEMA` = "'.cfm($database).'"
                           AND    `ROUTINE_TYPE`   = "FUNCTION"
                           AND    `ROUTINE_NAME`   = "'.cfm($name).'"', null, null, $connector);

            if (substr($query, 0, 1) == '!') {
                $not   = true;
                $query = substr($query, 1);

            } else {
                $not = false;
            }

            if (empty($return) xor $not) {
                return false;
            }

            if ($query) {
                sql_query($query, null, $connector);
            }

            return $return;

        }catch(Exception $e) {
            throw new OutOfBoundsException('sql_function_exists(): Failed', $e);
        }
    }



    /*
     * Returns the tables that have foreign keys to the specified table / column
     *
     * @author Sven Oostenbrink <support@capmega.com>
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @category Function reference
     * @package sql-exists
     *
     * @param string $table The table on which the foreign keys should be found
     * @param string $column The column on which the foreign keys should be found
     * @param null mixed $connector If specified, executed this function on the specified database connector. If not specified, use the current database connector
     * @return array The foreign keys for the specified table and column
     */

    public static function listForeignKeys($table, $column = null, $connector = null) {
        try {
            $list = sql_query('SELECT TABLE_NAME,
                                  COLUMN_NAME,
                                  CONSTRAINT_NAME,
                                  REFERENCED_TABLE_NAME,
                                  REFERENCED_COLUMN_NAME

                           FROM   INFORMATION_SCHEMA.KEY_COLUMN_USAGE

                           WHERE  REFERENCED_TABLE_NAME = "' . $table.'"
             '.($column ? 'AND    REFERENCED_COLUMN_NAME = "' . $column.'"' : '' ).';', null, $connector);

            return $list;

        }catch(Exception $e) {
            throw new OutOfBoundsException('sql_list_foreignkeys(): Failed', $e);
        }
    }
}