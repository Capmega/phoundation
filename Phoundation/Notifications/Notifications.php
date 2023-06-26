<?php

declare(strict_types=1);

namespace Phoundation\Notifications;

use PDOStatement;
use Phoundation\Core\Arrays;
use Phoundation\Core\Session;
use Phoundation\Core\Strings;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Databases\Sql\QueryBuilder;
use Phoundation\Databases\Sql\Sql;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\SelectInterface;
use Phoundation\Web\Http\Html\Components\Input\InputSelect;


/**
 * Notifications class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundations\Notifications
 */
class Notifications extends DataList
{
    /**
     * Notifications class constructor
     */
    public function __construct()
    {
        $this->entry_class = Notification::class;
        $this->table       = 'notifications';

        $this->setQuery('SELECT   `id`, `title`, `mode` AS `severity`, `priority`, `created_on` 
                                   FROM     `notifications` 
                                   WHERE    `users_id` = :users_id 
                                     AND    `status` IS NULL 
                                   ORDER BY `title`', [':users_id' => Session::getUser()->getId()]);

        parent::__construct();
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
        $this->source = $this->loadDetails($columns, $filters, $order_by);
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

        foreach ($this->source as $entry) {
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
    public function load(?string $id_column = null): static
    {
        $this->source = sql()->list('SELECT `notifications`.`id`, `notifications`.`title`  
                                   FROM     `notifications` 
                                   WHERE    `notifications`.`status` IS NULL
                                   ORDER BY `created_on`');

        return $this;
    }


    /**
     * @inheritDoc
     */
    public function loadDetails(array|string|null $columns, array $filters = [], array $order_by = []): array
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
        $builder->addSelect($columns);
        $builder->addFrom('`notifications`');

        // Add ordering
        foreach ($order_by as $column => $direction) {
            $builder->addOrderBy('`' . $column . '` ' . ($direction ? 'DESC' : 'ASC'));
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


    /**
     * Returns an HTML <select> for the available object entries
     *
     * @param string $value_column
     * @param string $key_column
     * @return SelectInterface
     */
    public function getHtmlSelect(string $value_column = 'name', string $key_column = 'id'): SelectInterface
    {
        return InputSelect::new()
            ->setSourceQuery('SELECT   `' . $key_column . '`, `' . $value_column . '` 
                                         FROM     `' . $this->table . '` 
                                         WHERE    `status` IS NULL 
                                         ORDER BY `title` ASC')
            ->setName('notifications_id')
            ->setNone(tr('Select a notification'))
            ->setEmpty(tr('No notifications available'));
    }
}