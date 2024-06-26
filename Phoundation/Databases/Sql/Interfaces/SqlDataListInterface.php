<?php

namespace Phoundation\Databases\Sql\Interfaces;

use Phoundation\Data\DataEntry\Interfaces\DataIteratorInterface;

/**
 * Class SqlDataIterator
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */
interface SqlDataIteratorInterface
{
    /**
     * Returns the Sql object used by this SqlDataIterator object
     *
     * @return SqlInterface
     */
    public function getSql(): SqlInterface;


    /**
     * Sets the Sql object used by this SqlDataIterator object
     *
     * @param SqlInterface $sql
     *
     * @return static
     */
    public function setSql(SqlInterface $sql): static;


    /**
     * Sets the data list
     *
     * @param DataIteratorInterface $data_list
     *
     * @return static
     */
    public function setDataIterator(DataIteratorInterface $data_list): static;


    /**
     * Update the status for the data row in the specified table to "deleted"
     *
     * This is a simplified insert method to speed up writing basic insert queries
     *
     * @note This method assumes that the specifies rows are correct to the specified table. If columns not pertaining
     *       to this table are in the $row value, the query will automatically fail with an exception!
     *
     * @param array       $row
     * @param string|null $comments
     *
     * @return int
     */
    public function delete(array $row, ?string $comments = null): int;


    /**
     * Update the status for the data row in the specified table to the specified status
     *
     * @param string|null                 $status
     * @param DataIteratorInterface|array $list
     * @param string|null                 $comments
     *
     * @return int
     */
    public function setStatus(?string $status, DataIteratorInterface|array $list, ?string $comments = null): int;


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
    public function exists(string $column, string|int|null $value, ?int $id = null): bool;
}