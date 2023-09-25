<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry;

use Phoundation\Core\Arrays;
use Phoundation\Data\DataEntry\Interfaces\ListOperationsInterface;


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
     * Returns a new ListOperations object
     *
     * @param string $parent_class
     * @return static
     */
    public static function new(string $parent_class): static
    {
        return new static($parent_class);
    }


    /**
     * Set the specified status for the specified entries
     *
     * @todo Optimize this function
     * @param array|string $ids
     * @param string|null $status
     * @param string|null $comments
     * @return int
     */
    public function setStatus(array|string $ids, ?string $status, ?string $comments = null): int
    {
        $count = 0;

        foreach (Arrays::force($ids) as $id) {
            $count++;
            $entry = $this->parent::getEntryClass()::new($id, 'id');
            $entry->setStatus($status);
        }

        return $count;
    }


    /**
     * Delete the specified entries
     *
     * @param array|string $ids
     * @param string|null $comments
     * @return int
     */
    public function delete(array|string $ids, ?string $comments = null): int
    {
        return $this->setStatus($ids, 'deleted', $comments);
    }


    /**
     * Erase (as in SQL DELETE) the specified entries from the database, also erasing their meta data
     *
     * @param array|string $ids
     * @return int
     */
    public function erase(array|string $ids): int
    {
        $meta = [];

        // Delete the meta data entries
        foreach (Arrays::force($ids) as $id) {
            $count++;
            $entry = $this->parent::getEntryClass()::new($id, 'id');
            $entry->erase();
        }

        return $count;
    }


    /**
     * Undelete the specified entries
     *
     * @note This will set the status "NULL" to the entries in this datalist, NOT the original value of their status!
     * @param string|null $comments
     * @return int
     */
    public function undelete(array|string $ids, ?string $comments = null): int
    {
        return $this->setStatus($ids, null, $comments);
    }
}
