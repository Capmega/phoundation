<?php

declare(strict_types=1);

namespace Phoundation\Databases\Sql;

use Exception;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Meta\Meta;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\DataEntry\Interfaces\DataListInterface;
use Phoundation\Data\Traits\TraitDataDataList;
use Phoundation\Data\Traits\TraitDataIdColumn;
use Phoundation\Data\Traits\TraitDataInsertUpdate;
use Phoundation\Data\Traits\TraitDataMaxIdRetries;
use Phoundation\Data\Traits\TraitDataMetaEnabled;
use Phoundation\Data\Traits\TraitDataRandomId;
use Phoundation\Data\Traits\TraitDataTable;
use Phoundation\Databases\Sql\Exception\SqlDuplicateException;
use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Databases\Sql\Interfaces\SqlDataListInterface;
use Phoundation\Databases\Sql\Interfaces\SqlInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;


/**
 * Class SqlDataList
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
class SqlDataList implements SqlDataListInterface
{
    use TraitDataDataList {
        setDataList as protected __setDataList;
    }
    use TraitDataIdColumn;
    use TraitDataInsertUpdate;
    use TraitDataMaxIdRetries;
    use TraitDataMetaEnabled;
    use TraitDataRandomId;
    use TraitDataTable;


    /**
     * The actual SQL connector
     *
     * @var SqlInterface $sql
     */
    protected SqlInterface $sql;


    /**
     * Sets how many times some failures may be retried until an exception is thrown
     *
     * @var int $max_id_retries
     */
    protected int $max_id_retries = 5;


    /**
     * SqlDataList class constructor
     *
     * @param SqlInterface $sql
     * @param DataListInterface $data_list
     */
    public function __construct(SqlInterface $sql, DataListInterface $data_list) {
        $this->setSql($sql)
             ->setDataList($data_list);
    }


    /**
     * Returns a new SqlDataList object
     *
     * @param SqlInterface $sql
     * @param DataListInterface $data_list
     * @return static
     */
    public static function new(SqlInterface $sql, DataListInterface $data_list): static
    {
        return new static($sql, $data_list);
    }


    /**
     * Returns the Sql object used by this SqlDataList object
     *
     * @return SqlInterface
     */
    public function getSql(): SqlInterface
    {
        return $this->sql;
    }


    /**
     * Sets the Sql object used by this SqlDataList object
     *
     * @param SqlInterface $sql
     * @return static
     */
    public function setSql(SqlInterface $sql): static
    {
        $this->sql = $sql;
        return $this;
    }


    /**
     * Sets the data list
     *
     * @param DataListInterface $data_list
     * @return static
     */
    public function setDataList(DataListInterface $data_list): static
    {
        $this->setTable($data_list->getTable())
             ->setIdColumn($data_list->getIdColumn())
             ->setRandomId($data_list->getRandomId())
             ->setMetaEnabled($data_list->getMetaEnabled())
             ->setInsertUpdate($data_list->getInsertUpdate())
             ->setMaxIdRetries($data_list->getMaxIdRetries());

        return $this->__setDataList($data_list);
    }


    /**
     * Returns whether to use INSERT ON DUPLICATE KEY UPDATE queries instead of insert / update
     *
     * @return int
     */
    public function getMaxIdRetries(): int
    {
        return $this->max_id_retries;
    }


    /**
     * Sets whether to use INSERT ON DUPLICATE KEY UPDATE queries instead of insert / update
     *
     * @param int $max_id_retries
     * @return static
     */
    public function setMaxIdRetries(int $max_id_retries): static
    {
        $this->max_id_retries = $max_id_retries;
        return $this;
    }


    /**
     * Write the specified data row in the specified table
     *
     * This is a simplified insert / update method to speed up writing basic insert or update queries. If the
     * $update_row[id] contains a value, the method will try to update instead of insert
     *
     * @note This method assumes that the specified rows are correct to the specified table. If columns not pertaining
     *       to this table are in the $row value, the query will automatically fail with an exception!
     * @param array $insert_row
     * @param array $update_row
     * @param string|null $comments
     * @param string|null $diff
     * @return int
     */
    public function write(array $insert_row, array $update_row, ?string $comments, ?string $diff): int
    {
        // New list, insert
        $retry = 0;

        while ($retry++ < $this->max_id_retries) {
            if ($this->random_id) {
                try {
                    // Create a random table ID
                    $random_id = random_int(1, PHP_INT_MAX);

                } catch (Exception $e) {
                    throw SqlException::new(tr('Failed to create random table ID'), $e);
                }
            } else {
                $random_id = null;
            }

            try {
                if ($this->insert_update) {
                    // Insert / Update the row
                    $insert_row = Arrays::prepend($insert_row, $this->id_column, $random_id);
                    return $this->insertUpdate($insert_row, $update_row, $comments, $diff);

                } else {
                    // Insert the row
                    $insert_row = Arrays::prepend($insert_row, $this->id_column, $random_id);
                    return $this->insert($insert_row, $comments, $diff);
                }

            } catch (SqlException $e) {
                if ($e->getCode() !== 1062) {
                    // Some different error, keep throwing
                    throw $e;
                }

                // Duplicate list, which?
                $column = $e->getMessage();
                $column = Strings::until(Strings::fromReverse($column, 'key \''), '\'');
                $column = Strings::from($column, '.');
                $column = trim($column);

                if ($column === $this->id_column) {
                    // Duplicate ID, try with a different random number
                    Log::warning($this->sql->getConnectorLogPrefix() . tr('Wow! Duplicate ID list ":rowid" encountered for insert in table ":table", retrying', [
                        ':rowid' => $insert_row[$this->id_column],
                        ':table' => $this->table
                    ]));

                    continue;
                }

                // Duplicate another column, continue throwing
                throw new SqlDuplicateException(tr('Duplicate list encountered for column ":column"', [
                    ':column' => $column
                ]), $e);
            }
        }

        // If the randomly selected ID already exists, try again
        throw new SqlException(tr('Could not find a unique id in ":retries" retries', [
            ':retries' => $this->max_id_retries
        ]));
    }


    /**
     * Initializes the specified row for an INSERT operation
     *
     * @param array $row
     * @param string|null $comments
     * @param string|null $diff
     * @return array
     * @throws Exception
     */
    protected function initializeInsertRow(array $row, ?string $comments, ?string $diff): array
    {
        // Set meta fields
        if ($this->data_list->isMetaColumn('meta_id')) {
            $row['meta_id'] = ($this->meta_enabled ? Meta::init($comments, $diff)->getId() : null);
        }

        if ($this->data_list->isMetaColumn('created_by')) {
            $row['created_by'] = Session::getUser()->getId();
        }

        if ($this->data_list->isMetaColumn('meta_state')) {
            $row['meta_state'] = Strings::getRandom(16);
        }

        unset($row['created_on']);
        return $row;
    }


    /**
     * Initializes the specified row for an UPDATE operation
     *
     * @param array $row
     * @param string|null $comments
     * @param string|null $diff
     * @param string $meta_action
     * @return array
     * @throws Exception
     */
    protected function initializeUpdateRow(array $row, ?string $comments, ?string $diff, string $meta_action): array
    {
        // Log meta_id action
        if ($this->data_list->isMetaColumn('meta_id')) {
            if ($this->meta_enabled) {
                Meta::get($row['meta_id'])->action($meta_action, $comments, $diff);
            }
        }

        if ($this->data_list->isMetaColumn('meta_state')) {
            $row['meta_state'] = Strings::getRandom(16);
        }

        // Never update the other meta-information
        foreach ($this->data_list->getMetaColumns() as $column) {
            if ($column === $this->id_column) {
                // We DO need the ID column for update, though!
                continue;
            }

            unset($row[$column]);
        }

        return $row;
    }


    /**
     * Insert the specified data row in the specified table
     *
     * This is a simplified insert method to speed up writing basic insert queries
     *
     * @note: PDO::lastInsertId() returns string|false, this method will return int
     * @note This method assumes that the specified rows are correct to the specified table. If columns not pertaining
     *       to this table are in the $row value, the query will automatically fail with an exception!
     * @param array $row
     * @param string|null $comments
     * @param string|null $diff
     * @return int|null
     * @throws Exception
     */
    public function insert(array $row, ?string $comments = null, ?string $diff = null): ?int
    {
        Core::checkReadonly('sql data-list-insert');

        // Set meta fields for insert
        $row = static::initializeInsertRow($row, $comments, $diff);

        // Build bound variables for the query
        $columns = SqlQueries::getPrefixedColumns($row, $this->data_list->getColumnPrefix());
        $values  = SqlQueries::getBoundValues($row, $this->data_list->getColumnPrefix(), true);
        $keys    = SqlQueries::getBoundKeys($row);

        $this->sql->query('INSERT INTO `' . $this->table . '` (' . $columns . ')
                                 VALUES                             (' . $keys    . ')', $values);

        if (empty($row[$this->id_column])) {
            // No row id specified, get the insert id from SQL driver
            return $this->sql->getInsertId();
        }

        // Use the given row id
        return $row[$this->id_column];
    }


    /**
     * Insert the specified data row in the specified table
     *
     * This is a simplified insert method to speed up writing basic insert queries
     *
     * @note: PDO::lastInsertId() returns string|false, this method will return int
     * @note This method assumes that the specifies rows are correct to the specified table. If columns not pertaining
     *       to this table are in the $row value, the query will automatically fail with an exception!
     * @param array $insert_row
     * @param array $update_row
     * @param string|null $comments
     * @param string|null $diff
     * @param string $meta_action
     * @return int|null
     * @throws Exception
     */
    public function insertUpdate(array $insert_row, array $update_row, ?string $comments = null, ?string $diff = null, string $meta_action = 'update'): ?int
    {
        Core::checkReadonly('sql data-list-insert-update');

        // Set meta fields for insert
        $insert_row = static::initializeInsertRow($insert_row, $comments, $diff);
        $update_row = static::initializeUpdateRow($update_row, $comments, $diff, $meta_action);

        // Build variables for the insert part of the query
        $insert_columns = SqlQueries::getPrefixedColumns($insert_row, $this->data_list->getColumnPrefix());
        $insert_values  = SqlQueries::getBoundValues($insert_row, $this->data_list->getColumnPrefix(), true);
        $keys           = SqlQueries::getBoundKeys($insert_row);

        // Build variables for the update part of the query
        $updates       = SqlQueries::getUpdateKeyValues($update_row, 'update_' . $this->data_list->getColumnPrefix(), $this->id_column);
        $update_values = SqlQueries::getBoundValues($update_row, 'update_' . $this->data_list->getColumnPrefix(), false, [$this->id_column]);
        $execute       = array_merge($insert_values, $update_values);

//        show($this->table);
//        show($insert_row);
//        show($update_row);
//        show($insert_columns);
//        show($keys);
//        show($updates);
//        show($execute);
//        show('INSERT INTO            `' . $this->table . '` (' . $insert_columns . ')
//                                 VALUES                                        (' . $keys           . ')
//                                 ON DUPLICATE KEY UPDATE ' . $updates);
//        showdie(SqlQueries::buildQueryString('INSERT INTO            `' . $this->table . '` (' . $insert_columns . ')
//                                 VALUES                                        (' . $keys           . ')
//                                 ON DUPLICATE KEY UPDATE ' . $updates, $execute));

        $this->sql->query('INSERT INTO            `' . $this->table . '` (' . $insert_columns . ')
                                 VALUES                                        (' . $keys           . ')
                                 ON DUPLICATE KEY UPDATE ' . $updates, $execute);

        if (empty($insert_row[$this->id_column])) {
            // No row id specified, get the insert id from SQL driver
            return $this->sql->getInsertId();
        }

        // Use the given row id
        return $insert_row[$this->id_column];
    }


    /**
     * Update the specified data row in the specified table
     *
     * This is a simplified insert method to speed up writing basic insert queries
     * @note This method assumes that the specifies rows are correct to the specified table. If columns not pertaining
     *       to this table are in the $row value, the query will automatically fail with an exception!
     * @param array $row
     * @param string|null $comments
     * @param string|null $diff
     * @param string $meta_action
     * @return int|null
     * @throws Exception
     */
    public function update(array $row, ?string $comments = null, ?string $diff = null, string $meta_action = 'update'): ?int
    {
        Core::checkReadonly('sql data-list-update');

        // Set meta fields for update
        static::initializeUpdateRow($row, $comments, $diff, $meta_action);

        // Build bound variables for the query
        $update = SqlQueries::getUpdateKeyValues($row, id_column: $this->id_column);
        $values = SqlQueries::getBoundValues($row);

        $this->sql->query('UPDATE `' . $this->table . '`
                                 SET     ' . $update  . '
                                 WHERE  `' . $this->id_column . '` = :' . $this->id_column, $values);

        return $row[$this->id_column];
    }


    /**
     * Update the status for the data row in the specified table to "deleted"
     *
     * This is a simplified insert method to speed up writing basic insert queries
     *
     * @note This method assumes that the specifies rows are correct to the specified table. If columns not pertaining
     *       to this table are in the $row value, the query will automatically fail with an exception!
     * @param array $row
     * @param string|null $comments
     * @return int
     */
    public function delete(array $row, ?string $comments = null): int
    {
        Core::checkReadonly('sql data-list-delete');

        // DataList table?
        if (array_key_exists('meta_id', $row)) {
            return $this->setStatus('deleted', $row, $comments);
        }

        // This table is not a DataList table, delete the list
        return $this->sql->delete($this->table, $row);
    }


    /**
     * Update the status for the data row in the specified table to the specified status
     *
     * @param string|null $status
     * @param DataListInterface|array $list
     * @param string|null $comments
     * @return int
     */
    public function setStatus(?string $status, DataListInterface|array $list, ?string $comments = null): int
    {
        Core::checkReadonly('sql set-status');

        if (is_object($list)) {
            $list = [
                $this->id_column => $list->getId(),
                'meta_id'        => $list->getMetaId(),
            ];
        }

        if (empty($list[$this->id_column])) {
            throw new OutOfBoundsException(tr('Cannot set status, no row id specified'));
        }

        // Update the meta data
        if ($this->meta_enabled) {
            Meta::get($list['meta_id'], false)->action(tr('Changed status'), $comments, Json::encode([
                'status' => $status
            ]));
        }

        // Update the row status
        return $this->sql->query('UPDATE `' . $this->table . '`
                                   SET     `status`             = :status
                                   WHERE   `' . $this->id_column . '` = :' . $this->id_column, [
            ':status'              => $status,
            ':' . $this->id_column => $list[$this->id_column]
        ])->rowCount();
    }


    /**
     * Simple "Does a row with this value exist in that table" method
     *
     * @param string $column
     * @param string|int|null $value
     * @param int|null $id ONLY WORKS WITH TABLES HAVING `id` column! (almost all do) If specified, will NOT select the
     *                     row with this id
     * @return bool
     */
    public function exists(string $column, string|int|null $value, ?int $id = null): bool
    {
        if ($id) {
            return (bool) $this->get('SELECT `id` FROM `' . $this->table . '` WHERE `' . $column . '` = :' . $column . ' AND `' . $this->id_column . '` != :' . $this->id_column, [
                ':' . $column          => $value,
                ':' . $this->id_column => $id
            ]);
        }

        return (bool) $this->get('SELECT `id` FROM `' . $this->table . '` WHERE `' . $column . '` = :' . $column, [$column => $value]);
    }
}