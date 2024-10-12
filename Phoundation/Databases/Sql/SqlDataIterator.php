<?php

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


declare(strict_types=1);

namespace Phoundation\Databases\Sql;

use Phoundation\Core\Core;
use Phoundation\Core\Meta\Meta;
use Phoundation\Data\DataEntry\Interfaces\DataIteratorInterface;
use Phoundation\Data\Traits\TraitDataDataIterator;
use Phoundation\Data\Traits\TraitDataDebug;
use Phoundation\Data\Traits\TraitDataIdColumn;
use Phoundation\Data\Traits\TraitDataInsertUpdate;
use Phoundation\Data\Traits\TraitDataMaxIdRetries;
use Phoundation\Data\Traits\TraitDataMetaEnabled;
use Phoundation\Data\Traits\TraitDataRandomId;
use Phoundation\Data\Traits\TraitDataTable;
use Phoundation\Databases\Sql\Interfaces\SqlDataIteratorInterface;
use Phoundation\Databases\Sql\Interfaces\SqlInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Json;


class SqlDataIterator implements SqlDataIteratorInterface
{
    use TraitDataDataIterator {
        setDataIterator as protected __setDataIterator;
    }
    use TraitDataDebug;
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
     * SqlDataIterator class constructor
     *
     * @param SqlInterface          $sql
     * @param DataIteratorInterface $data_iterator
     */
    public function __construct(SqlInterface $sql, DataIteratorInterface $data_iterator)
    {
        $this->setSql($sql)
             ->setDataIterator($data_iterator);
    }


    /**
     * Sets the data list
     *
     * @param DataIteratorInterface $data_iterator
     *
     * @return static
     */
    public function setDataIterator(DataIteratorInterface $data_iterator): static
    {
        $this->setTable($data_iterator->getTable())
             ->setIdColumn($data_iterator->getUniqueColumn());

        return $this->__setDataIterator($data_iterator);
    }


    /**
     * Returns a new SqlDataIterator object
     *
     * @param SqlInterface          $sql
     * @param DataIteratorInterface $data_list
     *
     * @return static
     */
    public static function new(SqlInterface $sql, DataIteratorInterface $data_list): static
    {
        return new static($sql, $data_list);
    }


    /**
     * Returns the Sql object used by this SqlDataIterator object
     *
     * @return SqlInterface
     */
    public function getSql(): SqlInterface
    {
        return $this->sql;
    }


    /**
     * Sets the Sql object used by this SqlDataIterator object
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
    public function delete(array $row, ?string $comments = null): int
    {
        Core::checkReadonly('sql data-list-delete');

        // DataIterator table?
        if (array_key_exists('meta_id', $row)) {
            return $this->setStatus('deleted', $row, $comments);
        }

        // This table is not a DataIterator table, delete the list
        return $this->sql->setDebug($this->debug)
                         ->delete($this->table, $row);
    }


    /**
     * Update the status for the data row in the specified table to the specified status
     *
     * @param string|null                 $status
     * @param DataIteratorInterface|array $list
     * @param string|null                 $comments
     *
     * @return int
     */
    public function setStatus(?string $status, DataIteratorInterface|array $list, ?string $comments = null): int
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
            Meta::get($list['meta_id'], false)
                ->action(tr('Changed status'), $comments, Json::encode([
                    'status' => $status,
                ]));
        }

        // Update the row status
        return $this->sql->setDebug($this->debug)
                         ->query('UPDATE `' . $this->table . '`
                                  SET    `status`                   = :status
                                  WHERE  `' . $this->id_column . '` = :' . $this->id_column, [
                                      ':status'              => $status,
                                      ':' . $this->id_column => $list[$this->id_column],
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
