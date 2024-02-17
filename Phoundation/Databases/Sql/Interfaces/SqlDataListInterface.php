<?php

namespace Phoundation\Databases\Sql\Interfaces;


use Exception;
use Phoundation\Data\DataEntry\Interfaces\DataListInterface;

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
interface SqlDataListInterface
{
    /**
     * Returns the Sql object used by this SqlDataList object
     *
     * @return SqlInterface
     */
    public function getSql(): SqlInterface;

    /**
     * Sets the Sql object used by this SqlDataList object
     *
     * @param SqlInterface $sql
     * @return static
     */
    public function setSql(SqlInterface $sql): static;

    /**
     * Sets the data list
     *
     * @param DataListInterface $data_list
     * @return static
     */
    public function setDataList(DataListInterface $data_list): static;

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
     * @throws Exception
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
     * @param string $meta_action
     * @return int|null
     * @throws Exception
     */
    public function insertUpdate(array $insert_row, array $update_row, ?string $comments = null, ?string $diff = null, string $meta_action = 'update'): ?int;

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
     * @param DataListInterface|array $list
     * @param string|null $comments
     * @return int
     */
    public function setStatus(?string $status, DataListInterface|array $list, ?string $comments = null): int;

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