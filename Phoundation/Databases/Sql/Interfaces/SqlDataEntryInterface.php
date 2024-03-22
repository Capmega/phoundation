<?php

namespace Phoundation\Databases\Sql\Interfaces;

use Exception;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;


/**
 * Class SqlDataEntry
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
interface SqlDataEntryInterface
{
    /**
     * Returns the id_column
     *
     * @return string|null
     */
    public function getIdColumn(): ?string;

    /**
     * Sets the id_column
     *
     * @param string|null $id_column
     * @return static
     */
    public function setIdColumn(?string $id_column): static;

    /**
     * Returns if the meta-system is enabled or disabled for this (type of) DataEntry
     *
     * @return bool
     */
    public function getMetaEnabled(): bool;

    /**
     * Sets if the meta-system is enabled or disabled for this (type of) DataEntry
     *
     * @param bool $meta_enabled
     * return static
     */
    public function setMetaEnabled(bool $meta_enabled): static;

    /**
     * Returns whether to use random_id
     *
     * @return bool
     */
    public function getRandomId(): bool;

    /**
     * Sets whether to use random_id
     *
     * @param bool $random_id
     * @return static
     */
    public function setRandomId(bool $random_id): static;

    /**
     * Returns the table
     *
     * @return string|null
     */
    public function getTable(): ?string;

    /**
     * Sets the table
     *
     * @param string|null $table
     * @return static
     */
    public function setTable(?string $table): static;

    /**
     * SqlDataEntry class constructor
     *
     * @param SqlInterface $sql
     * @param DataEntryInterface $data_entry
     */
    public function __construct(SqlInterface $sql, DataEntryInterface $data_entry);

    /**
     * Returns a new SqlDataEntry object
     *
     * @param SqlInterface $sql
     * @param DataEntryInterface $data_entry
     * @return static
     */
    public static function new(SqlInterface $sql, DataEntryInterface $data_entry): static;

    /**
     * Returns whether to use INSERT ON DUPLICATE KEY UPDATE queries instead of insert / update
     *
     * @return bool
     */
    public function getInsertUpdate(): bool;

    /**
     * Sets whether to use INSERT ON DUPLICATE KEY UPDATE queries instead of insert / update
     *
     * @param bool $insert_update
     * @return static
     */
    public function setInsertUpdate(bool $insert_update): static;

    /**
     * Returns whether to use INSERT ON DUPLICATE KEY UPDATE queries instead of insert / update
     *
     * @return int
     */
    public function getMaxIdRetries(): int;

    /**
     * Sets whether to use INSERT ON DUPLICATE KEY UPDATE queries instead of insert / update
     *
     * @param int $max_id_retries
     * @return static
     */
    public function setMaxIdRetries(int $max_id_retries): static;

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
    public function write(array $insert_row, array $update_row, ?string $comments, ?string $diff): int;

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
     */
    public function insert(array $row, ?string $comments = null, ?string $diff = null): ?int;

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
     * @return int|null
     */
    public function insertUpdate(array $insert_row, array $update_row, ?string $comments = null, ?string $diff = null): ?int;

    /**
     * Update the specified data row in the specified table
     *
     * This is a simplified insert method to speed up writing basic insert queries
     * @note This method assumes that the specifies rows are correct to the specified table. If columns not pertaining
     *       to this table are in the $row value, the query will automatically fail with an exception!
     * @param array $row
     * @param string $meta_action
     * @param string|null $comments
     * @param string|null $diff
     * @return int|null
     */
    public function update(array $row, ?string $comments = null, ?string $diff = null, string $meta_action = 'update'): ?int;

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
    public function delete(array $row, ?string $comments = null): int;

    /**
     * Update the status for the data row in the specified table to the specified status
     *
     * @param string|null $status
     * @param string|null $comments
     * @return int
     */
    public function setStatus(?string $status, ?string $comments = null): int;

    /**
     * Simple "Does a row with this value exist in that table" method
     *
     * @param string $column
     * @param string|int|null $value
     * @param int|null $id ONLY WORKS WITH TABLES HAVING `id` column! (almost all do) If specified, will NOT select the
     *                     row with this id
     * @return bool
     */
    public function exists(string $column, string|int|null $value, ?int $id = null): bool;
}