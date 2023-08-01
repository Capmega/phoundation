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
use Phoundation\Web\Http\Html\Enums\DisplayMode;


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
        $this->setQuery('SELECT   `id`, `title`, `mode` AS `severity`, `priority`, `created_on` 
                               FROM     `notifications` 
                               WHERE    `users_id` = :users_id 
                                 AND    `status`   = "UNREAD" 
                               ORDER BY `created_by` ASC', [':users_id' => Session::getUser()->getId()]);

        parent::__construct();
    }


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'notifications';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryClass(): string
    {
        return Notification::class;
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueField(): ?string
    {
        return null;
    }


//    /**
//     * Returns the query builder for this object
//     *
//     * @note This is an experimental function
//     * @param array|string|null $columns
//     * @param array $filters
//     * @param array $order_by
//     * @return void
//     */
//    public function loadList(array|string|null $columns = null, array $filters = [], array $order_by = []): void
//    {
//        $this->source = $this->loadDetails($columns, $filters, $order_by);
//    }


    /**
     * Returns the most important notification mode
     *
     * @return string
     */
    public function getMostImportantMode(): string
    {
        $list = [
            'notice'      => 1,
            'information' => 2,
            'success'     => 3,
            'warning'     => 4,
            'danger'      => 5,
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


//    /**
//     * @inheritDoc
//     */
//    public function load(?string $id_column = null): static
//    {
//        $this->source = sql()->list('SELECT `notifications`.`id`, `notifications`.`title`
//                                   FROM     `notifications`
//                                   WHERE    `notifications`.`status` IS NULL
//                                   ORDER BY `created_on`');
//
//        return $this;
//    }
//
//
//    /**
//     * @inheritDoc
//     */
//    public function loadDetails(array|string|null $columns, array $filters = [], array $order_by = []): array
//    {
//        // Default columns
//        if (!$columns) {
//            $columns = '`id`, `title`, `mode`, `priority`, `created_on`';
//        }
//
//        // Default ordering
//        if (!$order_by) {
//            $order_by = ['created_on' => false];
//        }
//
//        // Get column information
//        $columns = Strings::force($columns);
//
//        // Build query
//        $builder = new QueryBuilder();
//        $builder->addSelect($columns);
//        $builder->addFrom('`notifications`');
//
//        // Add ordering
//        foreach ($order_by as $column => $direction) {
//            $builder->addOrderBy('`' . $column . '` ' . ($direction ? 'DESC' : 'ASC'));
//        }
//
//        // Build filters
//        foreach ($filters as $key => $value){
//            switch ($key) {
//                case 'status':
//                    $builder->addWhere('`status`' . Sql::is($value, ':status'), [':status' => $value]);
//                    break;
//
//                case 'users_id':
//                    $builder->addWhere('`users_id`' . Sql::is($value, ':users_id'), [':users_id' => $value]);
//                    break;
//            }
//        }
//
//        return sql()->list($builder->getQuery(), $builder->getExecute());
//    }


    /**
     * Returns an HTML <select> for the available object entries
     *
     * @param string $value_column
     * @param string $key_column
     * @param string|null $order
     * @return SelectInterface
     */
    public function getHtmlSelect(string $value_column = 'name', string $key_column = 'id', ?string $order = null): SelectInterface
    {
        return InputSelect::new()
            ->setSourceQuery('SELECT   `' . $key_column . '`, `' . $value_column . '` 
                                         FROM     `' . static::getTable() . '` 
                                         WHERE    `status` IS NULL 
                                         ORDER BY `title` ASC')
            ->setName('notifications_id')
            ->setNone(tr('Select a notification'))
            ->setEmpty(tr('No notifications available'));
    }


    /**
     * Marks the severity column with a color class
     *
     * @return $this
     */
    public function markSeverityColumn(): static
    {
        return $this->addCallback(function (&$row, &$params) {
            if (!array_key_exists('severity', $row)) {
                return;
            }

            switch ($row['severity']) {
                case 'info':
                    $row['severity'] = '<span class="notification-info">' . tr('Info') . '</span>';
                    break;

                case 'warning':
                    $row['severity'] = '<span class="notification-warning">' . tr('Warning') . '</span>';
                    break;

                case 'success':
                    $row['severity'] = '<span class="notification-success">' . tr('Success') . '</span>';
                    break;

                case 'danger':
                    $row['severity'] = '<span class="notification-danger">' . tr('Danger') . '</span>';
                    break;

                default:
                    $row['severity'] = htmlentities($row['severity']);
                    $row['severity'] = str_replace(PHP_EOL, '<br>', $row['severity']);
            }

            $params['skiphtmlentities']['severity'] = true;
        });
    }
}