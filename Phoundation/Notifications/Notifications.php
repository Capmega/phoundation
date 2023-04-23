<?php

namespace Phoundation\Notifications;

use Phoundation\Core\Arrays;
use Phoundation\Core\Session;
use Phoundation\Core\Strings;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Databases\Sql\QueryBuilder;
use Phoundation\Databases\Sql\Sql;


/**
 * Notifications class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundations\Notifications
 */
class Notifications extends DataList
{
    /**
     * Notifications class constructor
     *
     * @param Notification|null $parent
     * @param string|null $id_column
     */
    public function __construct(?Notification $parent = null, ?string $id_column = null)
    {
        $this->entry_class = Notification::class;
        $this->table_name  = 'notifications';

        $this->setHtmlQuery('SELECT   `id`, `title`, `mode` AS `severity`, `priority`, `created_on` 
                                   FROM     `notifications` 
                                   WHERE    `users_id` = :users_id 
                                     AND    `status` IS NULL 
                                   ORDER BY `title`', [':users_id' => Session::getUser()->getId()]);

        parent::__construct($parent, $id_column);
    }


    /**
     * Returns the query builder for this object
     *
     * @note This is an experimental function
     * @param array|string|null $columns
     * @param array $filters
     * @param array $order_by
     * @return void
     */
    public function loadList(array|string|null $columns = null, array $filters = [], array $order_by = []): void
    {
        $this->list = $this->loadDetails($columns, $filters, $order_by);
    }



    /**
     * Returns the most important notification mode
     *
     * @return string
     */
    public function getMostImportantMode(): string
    {
        $list = [
            'UNKNOWN' => 1,
            'INFO'    => 2,
            'SUCCESS' => 3,
            'WARNING' => 4,
            'DANGER'  => 5,
        ];

        $return = 1;

        foreach ($this->list as $entry) {
            $priority = isset_get($list[isset_get($entry['mode'])]);

            if ($priority > $return) {
                $return = $priority;
            }
        }

        return array_search($return, $list);
    }


    /**
     * @inheritDoc
     */
    protected function load(string|int|null $id_column = null): static
    {
        $this->list = sql()->list('SELECT `notifications`.`id`, `notifications`.`title`  
                                   FROM     `notifications` 
                                   WHERE    `notifications`.`status` IS NULL
                                   ORDER BY `created_on`');

        return $this;
    }



    /**
     * @inheritDoc
     */
    protected function loadDetails(array|string|null $columns, array $filters = [], array $order_by = []): array
    {
        // Default columns
        if (!$columns) {
            $columns = '`id`, `title`, `mode`, `priority`, `created_on`';
        }

        // Default ordering
        if (!$order_by) {
            $order_by = ['created_on' => false];
        }

        // Get column information
        $columns = Strings::force($columns);

        // Build query
        $builder = new QueryBuilder();
        $builder->addSelect('SELECT ' . $columns);
        $builder->addFrom('FROM `notifications`');

        // Add ordering
        foreach ($order_by as $column => $direction) {
            $builder->addOrderBy('ORDER BY `' . $column . '` ' . ($direction ? 'DESC' : 'ASC'));
        }

        // Build filters
        foreach ($filters as $key => $value){
            switch ($key) {
                case 'status':
                    $builder->addWhere('`status`' . Sql::is($value, ':status'), [':status' => $value]);
                    break;

                case 'users_id':
                    $builder->addWhere('`users_id`' . Sql::is($value, ':users_id'), [':users_id' => $value]);
                    break;
            }
        }

        return sql()->list($builder->getQuery(), $builder->getExecute());
    }



    /**
     * @inheritDoc
     */
    public function save(): static
    {
        // TODO: Implement save() method.
    }
}