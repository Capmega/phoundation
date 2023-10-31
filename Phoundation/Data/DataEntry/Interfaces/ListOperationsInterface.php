<?php

namespace Phoundation\Data\DataEntry\Interfaces;


/**
 * Class ListOperations
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Data
 */
interface ListOperationsInterface
{
    /**
     * Returns a new ListOperations object
     *
     * @return static
     */
    public static function new(string $parent_class): static;

    /**
     * Set the specified status for the specified entries
     *
     * @param array|string $ids
     * @param string|null $status
     * @param string|null $comments
     * @return int
     */
    public function setStatusKeys(array|string $ids, ?string $status, ?string $comments = null): int;

    /**
     * Delete the specified entries
     *
     * @param array|string $ids
     * @param string|null $comments
     * @return int
     */
    public function deleteKeys(array|string $ids, ?string $comments = null): int;

    /**
     * Erase (as in SQL DELETE) the specified entries from the database, also erasing their meta data
     *
     * @param array|string $ids
     * @return int
     */
    public function eraseKeys(array|string $ids): int;

    /**
     * Undelete the specified entries
     *
     * @note This will set the status "NULL" to the entries in this datalist, NOT the original value of their status!
     * @param array|string $ids
     * @param string|null $comments
     * @return int
     */
    public function undeleteKeys(array|string $ids, ?string $comments = null): int;
}
