<?php

/**
 * Class TraitMethodBuildQuery
 *
 * Contains the ::buildWhereQuery() method, used by DataEntry / DataIterator to manually build queries
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;


trait TraitMethodBuildManualQuery
{
    /**
     * Builds the query parts for manual filtering
     *
     * @param array|string|int|null $identifiers
     * @param string|null           $where
     * @param string|null           $joins
     * @param string|null           $group
     * @param string|null           $order
     * @param array|null            $execute
     * @param string                $separator
     *
     * @return void
     * @todo EXPAND AND IMPROVE
     */
    protected static function buildManualQuery(array|string|int|null $identifiers, ?string &$where, ?string &$joins, ?string &$group, ?string &$order, ?array &$execute, string $separator = ' AND '): void
    {
        // Build the query parts
        $where   = [];
        $execute = [];
        $joins   = [];
        $order   = [];
        $group   = [];

        foreach (Arrays::force($identifiers) as $column => $value) {
            if (is_int($column)) {
                $column = 'id';
            }

            switch ($column[0]) {
                case '$':
                    // This is SQL
                    $column = substr($column, 1);
                    $column = strtoupper($column);

                    switch ($column) {
                        case 'ORDER':
                            foreach ($value as $order_column => $order_direction) {
                                $order[] = ' `' . $order_column . '` ' . strtoupper($order_direction) . ' ';
                            }
                            break;

                        case 'JOINS':
                    }

                    // This is SQL commands, skip it
                    continue 2;
            }

            $not = '';

            if (!is_data_scalar($value, true)) {
                throw new OutOfBoundsException(tr('Invalid value ":value" specified for column ":column", must be a data scalar (either string, integer, float, or null)', [
                    ':column' => $column,
                    ':value'  => $value,
                ]));
            }

            if (is_string($value) and $value) {
                switch ($value[0]) {
                    case '!':
                        // NOT this column
                        $not   = '!';
                        $value = substr($value, 1);
                        break;
                }

                if ($value === 'NULL') {
                    $where[] = '`' . static::getTable() . '`.`' . $column . '` IS ' . ($not ? ' NOT' : '') . ' NULL';
                    continue;
                }

            } elseif ($value === null) {
                $where[] = '`' . static::getTable() . '`.`' . $column . '` IS NULL';
                continue;
            }

            $where[]                = '`' . static::getTable() . '`.`' . $column . '` ' . $not . '= :' . $column;
            $execute[':' . $column] = $value;
        }

        $where = implode($separator, $where);
        $order = implode(','       , $order);
        $joins = implode(PHP_EOL   , $joins);
        $group = implode(','       , $group);

        if ($order) {
            $order = ' ORDER BY ' . $order . ' ' . PHP_EOL;
        }
    }
}
