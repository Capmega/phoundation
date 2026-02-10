<?php

/**
 * Class SqlDataEntry
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Databases\Sql;

use Exception;
use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Meta\Meta;
use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Data\Traits\TraitDataDataEntry;
use Phoundation\Data\Traits\TraitDataDebug;
use Phoundation\Data\Traits\TraitDataForce;
use Phoundation\Data\Traits\TraitDataIdColumn;
use Phoundation\Data\Traits\TraitDataInsertUpdate;
use Phoundation\Data\Traits\TraitDataMaxIdRetries;
use Phoundation\Data\Traits\TraitDataMetaEnabled;
use Phoundation\Data\Traits\TraitDataRandomId;
use Phoundation\Data\Traits\TraitDataTable;
use Phoundation\Databases\Sql\Exception\SqlContstraintDuplicateEntryException;
use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Databases\Sql\Interfaces\SqlDataEntryInterface;
use Phoundation\Databases\Sql\Interfaces\SqlInterface;
use Phoundation\Databases\Sql\QueryBuilder\QueryBuilder;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Json;
use Phoundation\Utils\Numbers;
use Phoundation\Utils\Strings;


class SqlDataEntry implements SqlDataEntryInterface
{
    use TraitDataDataEntry {
        setDataEntryObject as protected __setDataEntry;
    }
    use TraitDataDebug;
    use TraitDataIdColumn;
    use TraitDataInsertUpdate;
    use TraitDataMaxIdRetries;
    use TraitDataMetaEnabled;
    use TraitDataRandomId;
    use TraitDataTable;
    use TraitDataForce;


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
             ->setDataEntryObject($data_entry);
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
     * Sets the data entry
     *
     * @param DataEntryInterface $o_data_entry
     *
     * @return static
     */
    public function setDataEntryObject(DataEntryInterface $o_data_entry): static
    {
        $this->setTable($o_data_entry->getTable())
             ->setIdColumn($o_data_entry->getIdColumn())
             ->setRandomId($o_data_entry->getRandomId())
             ->setMetaEnabled($o_data_entry->getMetaEnabled())
             ->setInsertUpdate($o_data_entry->getInsertUpdate())
             ->setMaxIdRetries($o_data_entry->getMaxIdRetries());

        return $this->__setDataEntry($o_data_entry);
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
     * @param string|null $comments Optional comments that will be added to the meta history for the DataEntry
     *
     * @return array                Returns an array with the meta columns for the DataEntry
     */
    public function write(?string $comments): array
    {
        // New entry, insert
        $retry     = 0;
        $random_id = null;

        while ($retry++ < $this->max_id_retries) {
            try {
                return $this->writeEntry($comments);

            } catch (SqlException $e) {
                $this->handleWriteException($e);
                continue;
            }
        }

        // If the randomly selected ID already exists, try again
        throw new SqlException(tr('Could not find a unique id in ":retries" retries', [
            ':retries' => $this->max_id_retries,
        ]));
    }


    /**
     * Handles all SQL exceptions from the attempt to write the DataEntry to the database
     *
     * @param SqlException $e
     *
     * @return void
     * @throws Exception
     */
    protected function handleWriteException(SqlException $e): void
    {
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
            if (isset($insert)) {
                Log::warning($this->sql->getConnectorLogPrefix() . tr('Wow! Duplicate ID entry ":row_id" encountered for insert in table ":table", retrying', [
                        ':row_id' => $insert[$this->id_column],
                        ':table'  => $this->table,
                    ]));

            } else {
                Log::warning($this->sql->getConnectorLogPrefix() . tr('Wow! Duplicate ID entry encountered for insert in table ":table", retrying', [
                        ':table' => $this->table,
                    ]));
            }

            return;
        }

        // Duplicate another column, continue throwing
        throw new SqlContstraintDuplicateEntryException(tr('Duplicate entry encountered for column ":column"', [
            ':column' => $column,
        ]), $e);
    }


    /**
     * Returns a random id in between the dataentry lower and upper limits, or NULL if random ID's are disabled for the data entry
     *
     * @return int|null
     */
    protected function generateRandomId(): ?int
    {
        return $this->random_id ? Numbers::getRandomInt($this->o_data_entry->getIdLowerLimit(), $this->o_data_entry->getIdUpperLimit()) : null;
    }


    /**
     * Writes the DataEntry data to the database
     *
     * @param string|null $comments
     *
     * @return array
     * @throws Exception
     */
    protected function writeEntry(?string $comments): array
    {
        // Write the entry
        if ($this->insert_update) {
            // THIS OBJECT ALWAYS INSERT / UPDATES. Init the random table ID
            if ($this->random_id) {
                $random_id = Numbers::getRandomInt($this->o_data_entry->getIdLowerLimit(), $this->o_data_entry->getIdUpperLimit());
            }

            $update = $this->o_data_entry->getSourceForDatabase(false);
            $insert = $this->o_data_entry->getSourceForDatabase(true);

            // With these queries always do add the id column
            $insert[$this->o_data_entry->getIdColumn()] = ($update[$this->o_data_entry->getIdColumn()] ?? $this->generateRandomId());
            return $this->insertUpdate($insert, $update, $comments, $this->o_data_entry->getDiff());
        }

        if ($this->o_data_entry->isNew()) {
            // NEW ENTRY, INSERT. Init the random table ID
            $insert = $this->o_data_entry->getSourceForDatabase(true);
            $insert = Arrays::prepend($insert, $this->id_column, $this->generateRandomId());

            return $this->insert($insert, $comments, $this->o_data_entry->getDiff());
        }

        // EXISTING ENTRY, UPDATE
        return $this->update($this->o_data_entry->getSourceForDatabase(false), $comments, $this->o_data_entry->getDiff());
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
     * @return array
     */
    public function insertUpdate(array $insert_row, array $update_row, ?string $comments = null, ?string $diff = null, string $meta_action = 'update'): array
    {
        Core::checkReadonly('sql data-entry-insert-update');

        // Filter row and set meta fields for insert
        $update_row = static::initializeUpdateRow($update_row, $comments, $diff, $meta_action);
        $insert_row = static::initializeInsertRow($insert_row, $comments, $diff, array_get_safe($update_row, 'meta_state'));

        // Build variables for the insert part of the query
        $insert_columns = QueryBuilder::getPrefixedColumns($insert_row, $this->o_data_entry->getPrefix());
        $insert_values  = QueryBuilder::getBoundValues($insert_row, $this->o_data_entry->getPrefix(), true);
        $keys           = QueryBuilder::getBoundKeys($insert_row);

        // Build variables for the update part of the query
        $updates       = QueryBuilder::getUpdateKeyValues($update_row, 'update_' . $this->o_data_entry->getPrefix(), $this->id_column);
        $update_values = QueryBuilder::getBoundValues($update_row, 'update_' . $this->o_data_entry->getPrefix(), false, [$this->id_column]);
        $execute       = array_merge($insert_values, $update_values);

        $this->sql->setDebug($this->debug)
                  ->query('INSERT INTO            `' . $this->table . '` (' . $insert_columns . ')
                           VALUES                                        (' . $keys . ')
                           ON DUPLICATE KEY UPDATE ' . $updates, $execute);

        if (empty($insert_row[$this->id_column])) {
            // No row id specified, get the insert id from SQL driver
            $insert_row[$this->id_column] = $this->sql->getInsertId();
        }

        // Return the meta-columns for this insert/update action
        return Arrays::keepKeys($insert_row, $this->o_data_entry->getMetaColumns());
    }


    /**
     * Initializes the specified row for an INSERT operation
     *
     * @param array       $row
     * @param string|null $comments
     * @param string|null $diff
     * @param string|null $meta_state
     *
     * @return array
     */
    protected function initializeInsertRow(array $row, ?string $comments, ?string $diff, ?string $meta_state = null): array
    {
        // Filter out non modified rows
        if ($this->force) {
            $row = Arrays::keepKeys($row, array_merge($this->o_data_entry->getChangedColumns(), $this->o_data_entry->getMetaColumns()));
        }

        // Set meta fields
        if ($this->o_data_entry->isMetaColumn('meta_id')) {
            $row['meta_id'] = ($this->meta_enabled ? Meta::init($comments, $diff)->getId() : null);
        }

        if ($this->o_data_entry->isMetaColumn('created_by')) {
            $row['created_by'] = Session::getUserObject()->getId(false);
        }

        if ($this->o_data_entry->isMetaColumn('meta_state')) {
            $row['meta_state'] = $meta_state ?? Strings::getRandom(16);
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
        if (!$this->force) {
            // Only update changed entries
            $row = Arrays::keepKeys($row, array_merge($this->o_data_entry->getChangedColumns(), $this->o_data_entry->getMetaColumns()));
        }

        // Log meta_id action
        if ($this->o_data_entry->isMetaColumn('meta_id')) {
            if ($this->getMetaEnabled()) {
                Meta::get($row['meta_id'])
                    ->action($meta_action, $comments, $diff);
            }
        }

        if ($this->o_data_entry->isMetaColumn('meta_state')) {
            $row['meta_state'] = Strings::getRandom(16);
        }

        // Never update the other meta-information
        foreach ($this->o_data_entry->getMetaColumns() as $column) {
            if ($column === $this->id_column) {
                // We DO need the ID column for update, though!
                continue;
            }

            if ($column === 'status') {
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
     * @return array
     * @throws Exception
     */
    public function insert(array $row, ?string $comments = null, ?string $diff = null): array
    {
        Core::checkReadonly('sql data-entry-insert');

        // Set meta fields for insert
        $row = static::initializeInsertRow($row, $comments, $diff);

        // Build bound variables for the query
        $columns = QueryBuilder::getPrefixedColumns($row);
        $values  = QueryBuilder::getBoundValues($row, $this->o_data_entry->getPrefix(), true);
        $keys    = QueryBuilder::getBoundKeys($row, $this->o_data_entry->getPrefix());

        $this->sql->setDebug($this->debug)
                  ->query('INSERT INTO `' . $this->table . '` (' . $columns . ')
                           VALUES                             (' . $keys    . ')', $values);

        if (empty($row[$this->id_column])) {
            // No row id specified, get the insert id from SQL driver
            $row[$this->id_column] = $this->sql->getInsertId();
        }

        // Return the meta-columns for this insert action
        return Arrays::keepKeys($row, $this->o_data_entry->getMetaColumns());
    }


    /**
     * Update the specified data row in the specified table
     *
     * This is a simplified insert method to speed up writing basic insert queries
     *
     * @note This method assumes that the specified rows are correct to the specified table. If columns not pertaining
     *       to this table are in the $row value, the query will automatically fail with an exception!
     *
     * @param array       $row
     * @param string|null $comments
     * @param string|null $diff
     * @param string      $meta_action
     *
     * @return array
     */
    public function update(array $row, ?string $comments = null, ?string $diff = null, string $meta_action = 'update'): array
    {
        Core::checkReadonly('sql data-entry-update');

        // Filter row and set meta fields for update
        $row = static::initializeUpdateRow($row, $comments, $diff, $meta_action);

        // Build bound variables for the query
        $update = QueryBuilder::getUpdateKeyValues($row, id_column: $this->id_column);
        $values = QueryBuilder::getBoundValues($row);

        $this->sql->setDebug($this->debug)
                  ->query('UPDATE `' . $this->table . '`
                           SET     ' . $update . '
                           WHERE  `' . $this->id_column . '` = :' . $this->id_column, $values);

        // Return the meta-columns for this update action
        return Arrays::keepKeys($row, $this->o_data_entry->getMetaColumns());
    }


    /**
     * Update the status for the data row in the specified table to "deleted"
     *
     * This is a simplified insert method to speed up writing basic status update queries
     *
     * @param string|null $comments
     *
     * @return static
     */
    public function delete(?string $comments = null): static
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
     * @return SqlDataEntry
     */
    public function undelete(?string $comments = null): static
    {
        Core::checkReadonly('sql data-entry-undelete');
        return $this->setStatus(null, $comments);
    }


    /**
     * Actually erases the data entry record
     *
     * This method will erase both the data entry record and its meta data history
     *
     * @return static
     */
    public function erase(): static
    {
        Core::checkReadonly('sql data-entry-erase');

        // Erase the meta-history and entries
        Meta::get($this->o_data_entry->getMetaId())->erase();

        // Erase the record
        sql()->setDebug($this->debug)
                    ->query('DELETE FROM `' . $this->table . '` WHERE `' . $this->getIdColumn() . '` = :id', [
                        ':id' => $this->o_data_entry->get($this->getIdColumn()),
                    ]);

        return $this;
    }


    /**
     * Update the status for the data row in the specified table to the specified status
     *
     * @param string|null $status
     * @param string|null $comments
     *
     * @return SqlDataEntry
     */
    public function setStatus(?string $status, ?string $comments = null): static
    {
        Core::checkReadonly('sql set-status');

        $entry = $this->o_data_entry;

        if ($entry->isNew()) {
            throw new OutOfBoundsException(tr('Cannot set status, the specified data entry is new'));
        }

        // Update the meta data
        if ($this->getMetaEnabled()) {
            Meta::get($entry->getMetaId(), false)
                ->action(tr('Changed status'), $comments, Json::encode([
                    'status' => $status,
                ]));
        }

        // Update the row status
        $this->sql->setDebug($this->debug)
                         ->query('UPDATE `' . $this->table . '`
                                  SET     `status`                   = :status,
                                          `meta_state`               = :meta_state
                                  WHERE   `' . $this->id_column . '` = :' . $this->id_column, [
                                      ':status'              => $status,
                                      ':meta_state'          => $entry->getMetaState(),
                                      ':' . $this->id_column => $entry->getId(),
                         ]);

        return $this;
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
