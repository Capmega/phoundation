<?php

/**
 * Class TraitMethodBuildQuery
 *
 * Contains the ::buildWhereQuery() method, used by DataEntry / DataIterator to manually build queries
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Databases\Sql\QueryBuilder\QueryBuilder;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;


trait TraitMethodBuildManualQuery
{
    /**
     * Builds the query parts for manual filtering
     *
     * @param IdentifierInterface|array|string|int|null $identifiers
     * @param string|null                               $where
     * @param string|null                               $joins
     * @param string|null                               $group
     * @param string|null                               $order
     * @param array|null                                $execute
     * @param string                                    $separator
     *
     * @return void
     * @deprecated This method should not be relied upon anymore as the QueryBuilder class will take over this job.
     */
    protected static function buildManualQuery(IdentifierInterface|array|string|int|null $identifiers, ?string &$where, ?string &$joins, ?string &$group, ?string &$order, ?array &$execute, string $separator = ' AND '): void
    {
        // Build the query parts
        $where   = [];
        $execute = $execute ?? [];
        $joins   = [];
        $order   = [];
        $group   = [];

        foreach (Arrays::force($identifiers) as $column => $value) {
            if ($column === '') {
                continue;
            }

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

            $where = Arrays::appendNotNull($where, Strings::encapsulate(QueryBuilder::renderComparison(static::getTable(), $column, $value, $execute), '(', ')'));

//            $not = '';
//
//            if (!is_data_scalar($value, true)) {
//                if (!is_array($value)) {
//                    if ($value === false) {
//                        // FALSE indicates the column should not be filtered on, so ignore this column altogether
//                        continue;
//                    }
//
//                    throw new OutOfBoundsException(tr('Invalid query value ":value / :type" specified for column ":column", must be a data scalar (either string, integer, float, or null)', [
//                        ':column' => $column,
//                        ':value'  => $value,
//                        ':type'   =>  get_class_or_datatype($value),
//                    ]));
//                }
//
//                $in      = QueryBuilder::in($value, 'status');
//                $execute = array_merge($execute, $in);
//                $where[] = '`' . static::getTable() . '`.`' . $column . '` IN (' . implode(', ', array_keys($in)) . ')';
//                continue;
//
//            } elseif (is_string($value)) {
//                switch ($value[0]) {
//                    case '!':
//                        // NOT this column
//                        $not   = '!';
//                        $value = substr($value, 1);
//                        break;
//                }
//
//                if ($value === 'NULL') {
//                    $where[] = '`' . static::getTable() . '`.`' . $column . '` IS ' . ($not ? ' NOT' : '') . ' NULL';
//                    continue;
//                }
//
//            } elseif ($value === null) {
//                $where[] = '`' . static::getTable() . '`.`' . $column . '` IS NULL';
//                continue;
//            }
//
//            $where[]                = '`' . static::getTable() . '`.`' . $column . '` ' . $not . '= :' . $column;
//            $execute[':' . $column] = $value;
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
