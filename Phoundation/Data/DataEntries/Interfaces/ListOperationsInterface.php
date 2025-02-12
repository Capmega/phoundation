<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Interfaces;

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
     * @param string|null  $status
     * @param string|null  $comments
     *
     * @return static
     */
    public function setStatusKeys(array|string $ids, ?string $status, ?string $comments = null): static;


    /**
     * Delete the specified entries
     *
     * @param array|string $ids
     * @param string|null  $comments
     *
     * @return static
     */
    public function deleteKeys(array|string $ids, ?string $comments = null): static;


    /**
     * Erase (as in SQL DELETE) the specified entries from the database, also erasing their metadata
     *
     * @param array|string $ids
     *
     * @return int
     */
    public function eraseKeys(array|string $ids): static;


    /**
     * Undelete the specified entries
     *
     * @note This will set the status "NULL" to the entries in this datalist, NOT the original value of their status!
     *
     * @param array|string $ids
     * @param string|null  $comments
     *
     * @return int
     */
    public function undeleteKeys(array|string $ids, ?string $comments = null): static;
}
