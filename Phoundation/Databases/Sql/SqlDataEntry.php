<?php

/**
 * Class SqlDataEntry
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */

declare(strict_types=1);

namespace Phoundation\Databases\Sql;

use Exception;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Meta\Meta;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\Traits\TraitDataDataEntry;
use Phoundation\Data\Traits\TraitDataIdColumn;
use Phoundation\Data\Traits\TraitDataInsertUpdate;
use Phoundation\Data\Traits\TraitDataMaxIdRetries;
use Phoundation\Data\Traits\TraitDataMetaEnabled;
use Phoundation\Data\Traits\TraitDataRandomId;
use Phoundation\Data\Traits\TraitDataTable;
use Phoundation\Databases\Sql\Exception\SqlDuplicateException;
use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Databases\Sql\Interfaces\SqlDataEntryInterface;
use Phoundation\Databases\Sql\Interfaces\SqlInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Json;
use Phoundation\Utils\Numbers;
use Phoundation\Utils\Strings;

class SqlDataEntry implements SqlDataEntryInterface
{
    use TraitDataDataEntry {
        setDataEntry as protected __setDataEntry;
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
     * SqlDataEntry class constructor
     *
     * @param SqlInterface       $sql
     * @param DataEntryInterface $data_entry
     */
    public function __construct(SqlInterface $sql, DataEntryInterface $data_entry)
    {
        $this->setSql($sql)
             ->setDataEntry($data_entry);
    }


    /**
     * Sets the data entry
     *
     * @param DataEntryInterface $data_entry
     *
     * @return static
     */
    public function setDataEntry(DataEntryInterface $data_entry): static
    {
        $this->setTable($data_entry->getTable())
             ->setIdColumn($data_entry->getIdColumn())
             ->setRandomId($data_entry->getRandomId())
             ->setMetaEnabled($data_entry->getMetaEnabled())
             ->setInsertUpdate($data_entry->getInsertUpdate())
             ->setMaxIdRetries($data_entry->getMaxIdRetries());

        return $this->__setDataEntry($data_entry);
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
     *
     * @return static
     */
    public function setMaxIdRetries(int $max_id_retries): static
    {
        $this->max_id_retries = $max_id_retries;

        return $this;
    }


    /**
     * Returns a new SqlDataEntry object
     *
     * @param SqlInterface       $sql
     * @param DataEntryInterface $data_entry
     *
     * @return static
     */
    public static function new(SqlInterface $sql, DataEntryInterface $data_entry): static
    {
        return new static($sql, $data_entry);
    }


    /**
     * Returns the Sql object used by this SqlDataEntry object
     *
     * @return SqlInterface
     */
    public function getSql(): SqlInterface
    {
        return $this->sql;
    }


    /**
     * Sets the Sql object used by this SqlDataEntry object
     *
     * @param SqlInterface $sql
     *
     * @return static
     */
    public function setSql(SqlInterface $sql): static
    {
        $this->sql = $sql;

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
     *
     * @param string|null $comments
     * @param string|null $diff
     *
     * @return int
     */
    public function write(?string $comments, ?string $diff): int
    {
        // New entry, insert
        $retry     = 0;
        $random_id = null;

        while ($retry++ < $this->max_id_retries) {
            try {
                // Write the entry
                if ($this->insert_update) {
                    // THIS OBJECT ALWAYS INSERT / UPDATES. Init the random table ID
                    if ($this->random_id) {
                        $random_id = Numbers::getRandomInt($this->data_entry->getIdLowerLimit(), $this->data_entry->getIdUpperLimit());
                    }

                    $update = $this->data_entry->getSqlColumns(false);
                    $insert = $this->data_entry->getSqlColumns(true);

                    // With these queries always do add the id column
                    $insert[$this->data_entry->getIdColumn()] = ($update[$this->data_entry->getIdColumn()] ?? $random_id);
                    return $this->insertUpdate($insert, $update, $comments, $this->data_entry->getDiff());
                }

                if ($this->data_entry->isNew()) {
                    // NEW ENTRY, INSERT. Init the random table ID
                    if ($this->random_id) {
                        $random_id = Numbers::getRandomInt($this->data_entry->getIdLowerLimit(), $this->data_entry->getIdUpperLimit());
                    }

                    $insert = $this->data_entry->getSqlColumns(true);
                    $insert = Arrays::prepend($insert, $this->id_column, $random_id);

                    return $this->insert($insert, $comments, $this->data_entry->getDiff());
                }

                // EXISTING ENTRY, UPDATE
                return $this->update($this->data_entry->getSqlColumns(false), $comments, $this->data_entry->getDiff());

            } catch (SqlException $e) {
                if ($e->getCode() !== 1062) {
                    // Some different error, keep throwing
                    throw $e;
                }

                // Duplicate entry, which?
                $column = $e->getMessage();
                $column = Strings::until(Strings::fromReverse($column, 'key \''), '\'');
                $column = Strings::from($column, '.');
                $column = trim($column);

                if ($column === $this->id_column) {
                    // Duplicate ID, try with a different random number
                    Log::warning($this->sql->getConnectorLogPrefix() . tr('Wow! Duplicate ID entry ":rowid" encountered for insert in table ":table", retrying', [
                            ':rowid' => $insert_row[$this->id_column],
                            ':table' => $this->table,
                        ]));
                    continue;
                }

                // Duplicate another column, continue throwing
                throw new SqlDuplicateException(tr('Duplicate entry encountered for column ":column"', [
                    ':column' => $column,
                ]), $e);
            }
        }

        // If the randomly selected ID already exists, try again
        throw new SqlException(tr('Could not find a unique id in ":retries" retries', [
            ':retries' => $this->max_id_retries,
        ]));
    }


    /**
     * Insert the specified data row in the specified table
     *
     * This is a simplified insert method to speed up writing basic insert queries
     *
     * @note : PDO::lastInsertId() returns string|false, this method will return int
     * @note This method assumes that the specifies rows are correct to the specified table. If columns not pertaining
     *       to this table are in the $row value, the query will automatically fail with an exception!
     *
     * @param array       $insert_row
     * @param array       $update_row
     * @param string|null $comments
     * @param string|null $diff
     * @param string      $meta_action
     *
     * @return int|null
     */
    public function insertUpdate(array $insert_row, array $update_row, ?string $comments = null, ?string $diff = null, string $meta_action = 'update'): ?int
    {
        Core::checkReadonly('sql data-entry-insert-update');

        // Filter row and set meta fields for insert
        $insert_row = static::initializeInsertRow($insert_row, $comments, $diff);
        $update_row = static::initializeUpdateRow($update_row, $comments, $diff, $meta_action);

        // Build variables for the insert part of the query
        $insert_columns = SqlQueries::getPrefixedColumns($insert_row, $this->data_entry->getColumnPrefix());
        $insert_values  = SqlQueries::getBoundValues($insert_row, $this->data_entry->getColumnPrefix(), true);
        $keys           = SqlQueries::getBoundKeys($insert_row);

        // Build variables for the update part of the query
        $updates       = SqlQueries::getUpdateKeyValues($update_row, 'update_' . $this->data_entry->getColumnPrefix(), $this->id_column);
        $update_values = SqlQueries::getBoundValues($update_row, 'update_' . $this->data_entry->getColumnPrefix(), false, [$this->id_column]);
        $execute       = array_merge($insert_values, $update_values);

        $this->sql->query('INSERT INTO            `' . $this->table . '` (' . $insert_columns . ')
                                 VALUES                                        (' . $keys . ')
                                 ON DUPLICATE KEY UPDATE ' . $updates, $execute);

        if (empty($insert_row[$this->id_column])) {
            // No row id specified, get the insert id from SQL driver
            return $this->sql->getInsertId();
        }

        // Use the given row id
        return $insert_row[$this->id_column];
    }


    /**
     * Initializes the specified row for an INSERT operation
     *
     * @param array       $row
     * @param string|null $comments
     * @param string|null $diff
     *
     * @return array
     */
    protected function initializeInsertRow(array $row, ?string $comments, ?string $diff): array
    {
        // Filter out non modified rows
        $row = Arrays::keepKeys($row, array_merge($this->data_entry->getChanges(), $this->data_entry->getMetaColumns()));

        // Set meta fields
        if ($this->data_entry->isMetaColumn('meta_id')) {
            $row['meta_id'] = ($this->meta_enabled ? Meta::init($comments, $diff)->getId() : null);
        }

        if ($this->data_entry->isMetaColumn('created_by')) {
            $row['created_by'] = Session::getUser()->getId();
        }

        if ($this->data_entry->isMetaColumn('meta_state')) {
            $row['meta_state'] = Strings::getRandom(16);
        }

        // Created_on is always automatically set
        unset($row['created_on']);

        return $row;
    }


    /**
     * Initializes the specified row for an UPDATE operation
     *
     * @param array       $row
     * @param string|null $comments
     * @param string|null $diff
     * @param string      $meta_action
     *
     * @return array
     */
    protected function initializeUpdateRow(array $row, ?string $comments, ?string $diff, string $meta_action): array
    {
        // Filter out non modified rows
        $row = Arrays::keepKeys($row, array_merge($this->data_entry->getChanges(), $this->data_entry->getMetaColumns()));

        // Log meta_id action
        if ($this->data_entry->isMetaColumn('meta_id')) {
            if ($this->meta_enabled) {
                Meta::get($row['meta_id'])
                    ->action($meta_action, $comments, $diff);
            }
        }

        if ($this->data_entry->isMetaColumn('meta_state')) {
            $row['meta_state'] = Strings::getRandom(16);
        }

        // Never update the other meta-information
        foreach ($this->data_entry->getMetaColumns() as $column) {
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
     * @note : PDO::lastInsertId() returns string|false, this method will return int
     * @note This method assumes that the specified rows are correct to the specified table. If columns not pertaining
     *       to this table are in the $row value, the query will automatically fail with an exception!
     *
     * @param array       $row
     * @param string|null $comments
     * @param string|null $diff
     *
     * @return int
     * @throws Exception
     */
    public function insert(array $row, ?string $comments = null, ?string $diff = null): int
    {
        Core::checkReadonly('sql data-entry-insert');

        // Set meta fields for insert
        $row = static::initializeInsertRow($row, $comments, $diff);

        // Build bound variables for the query
        $columns = SqlQueries::getPrefixedColumns($row, $this->data_entry->getColumnPrefix());
        $values  = SqlQueries::getBoundValues($row, $this->data_entry->getColumnPrefix(), true);
        $keys    = SqlQueries::getBoundKeys($row);

        $this->sql->query('INSERT INTO `' . $this->table . '` (' . $columns . ')
                                 VALUES                             (' . $keys . ')', $values);

        if (empty($row[$this->id_column])) {
            // No row id specified, get the insert id from SQL driver
            return $this->sql->getInsertId();
        }

        // Use the given row id
        return $row[$this->id_column];
    }


    /**
     * Update the specified data row in the specified table
     *
     * This is a simplified insert method to speed up writing basic insert queries
     *
     * @note This method assumes that the specifies rows are correct to the specified table. If columns not pertaining
     *       to this table are in the $row value, the query will automatically fail with an exception!
     *
     * @param array       $row
     * @param string|null $comments
     * @param string|null $diff
     * @param string      $meta_action
     *
     * @return int
     */
    public function update(array $row, ?string $comments = null, ?string $diff = null, string $meta_action = 'update'): int
    {
        Core::checkReadonly('sql data-entry-update');

        // Filter row and set meta fields for update
        $row = static::initializeUpdateRow($row, $comments, $diff, $meta_action);

        // Build bound variables for the query
        $update = SqlQueries::getUpdateKeyValues($row, id_column: $this->id_column);
        $values = SqlQueries::getBoundValues($row);

        $this->sql->query('UPDATE `' . $this->table . '`
                                 SET     ' . $update . '
                                 WHERE  `' . $this->id_column . '` = :' . $this->id_column, $values);

        return $row[$this->id_column];
    }


    /**
     * Update the status for the data row in the specified table to "deleted"
     *
     * This is a simplified insert method to speed up writing basic status update queries
     *
     * @param string|null $comments
     *
     * @return int
     */
    public function delete(?string $comments = null): int
    {
        Core::checkReadonly('sql data-entry-delete');
        return $this->setStatus('deleted', $comments);
    }


    /**
     * Update the status for the data row in the specified table to NULL
     *
     * This is a simplified insert method to speed up writing basic status update queries
     *
     * @param string|null $comments
     *
     * @return int
     */
    public function undelete(?string $comments = null): int
    {
        Core::checkReadonly('sql data-entry-undelete');
        return $this->setStatus(null, $comments);
    }


    /**
     * Actually erases the data entry record
     *
     * This method will erase both the data entry record and its meta data history
     *
     * @return int
     */
    public function erase(): int
    {
        Core::checkReadonly('sql data-entry-erase');

        // Erase the meta-history and entries
        Meta::get($this->data_entry->getMetaId())->erase();

        // Erase the record
        return sql()->query('DELETE FROM `' . $this->table . '` WHERE `' . $this->getIdColumn() . '` = :id', [
            ':id' => $this->data_entry->get($this->getIdColumn()),
        ])->rowCount();
    }


    /**
     * Update the status for the data row in the specified table to the specified status
     *
     * @param string|null $status
     * @param string|null $comments
     *
     * @return int
     */
    public function setStatus(?string $status, ?string $comments = null): int
    {
        Core::checkReadonly('sql set-status');

        $entry = $this->data_entry;

        if ($entry->isNew()) {
            throw new OutOfBoundsException(tr('Cannot set status, the specified data entry is new'));
        }

        // Update the meta data
        if ($this->meta_enabled) {
            Meta::get($entry->getMetaId(), false)
                ->action(tr('Changed status'), $comments, Json::encode([
                    'status' => $status,
                ]));
        }

        // Update the row status
        return $this->sql->query('UPDATE `' . $this->table . '`
                                  SET     `status`             = :status
                                  WHERE   `' . $this->id_column . '` = :' . $this->id_column, [
            ':status'              => $status,
            ':' . $this->id_column => $entry->getId(),
        ])->rowCount();
    }


    /**
     * Simple "Does a row with this value exist in that table" method
     *
     * @param string          $column
     * @param string|int|null $value
     * @param int|null        $id ONLY WORKS WITH TABLES HAVING `id` column! (almost all do) If specified, will NOT
     *                            select the row with this id
     *
     * @return bool
     */
    public function exists(string $column, string|int|null $value, ?int $id = null): bool
    {
        if ($id) {
            return (bool) $this->get('SELECT `id` FROM `' . $this->table . '` WHERE `' . $column . '` = :' . $column . ' AND `' . $this->id_column . '` != :' . $this->id_column, [
                ':' . $column          => $value,
                ':' . $this->id_column => $id,
            ]);
        }

        return (bool) $this->get('SELECT `id` FROM `' . $this->table . '` WHERE `' . $column . '` = :' . $column, [$column => $value]);
    }
}