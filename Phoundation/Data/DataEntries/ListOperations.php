<?php

/**
 * Class ListOperations
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries;

use Phoundation\Data\DataEntries\Interfaces\ListOperationsInterface;
use Phoundation\Utils\Arrays;


class ListOperations implements ListOperationsInterface
{
    /**
     * The parent class on which the operations will be executed
     *
     * @var string $parent
     */
    protected string $parent;


    /**
     * ListOperations class constructor
     */
    public function __construct(string $parent_class)
    {
        $this->parent = $parent_class;
    }


    /**
     * Delete the specified entries
     *
     * @param array|string $ids
     * @param string|null  $comments
     *
     * @return static
     */
    public function deleteKeys(array|string $ids, ?string $comments = null): static
    {
        return $this->setStatusKeys($ids, 'deleted', $comments);
    }


    /**
     * Set the specified status for the specified entries
     *
     * @param array|string $ids
     * @param string|null  $status
     * @param string|null  $comments
     *
     * @return static
     */
    public function setStatusKeys(array|string $ids, ?string $status, ?string $comments = null): static
    {
        foreach (Arrays::force($ids) as $id) {
            $entry = $this->parent::getEntryClass()::new($id);
            $entry->setStatus($status);
        }

        return $this;
    }


    /**
     * Returns a new ListOperations object
     *
     * @param string $parent_class
     *
     * @return static
     */
    public static function new(string $parent_class): static
    {
        return new static($parent_class);
    }


    /**
     * Erase (as in SQL DELETE) the specified entries from the database, also erasing their meta data
     *
     * @param array|string $ids
     *
     * @return static
     */
    public function eraseKeys(array|string $ids): static
    {
        $meta = [];
        // Delete the meta data entries
        foreach (Arrays::force($ids) as $id) {
            $entry = $this->parent::getEntryClass()::new($id);
            $entry->erase();
        }

        return $this;
    }


    /**
     * Undelete the specified entries
     *
     * @note This will set the status "NULL" to the entries in this datalist, NOT the original value of their status!
     *
     * @param array|string $ids
     * @param string|null  $comments
     *
     * @return ListOperations
     */
    public function undeleteKeys(array|string $ids, ?string $comments = null): static
    {
        return $this->setStatusKeys($ids, null, $comments);
    }
}
