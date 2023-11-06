<?php

declare(strict_types=1);

namespace Phoundation\Core\Meta;

use Phoundation\Core\Arrays;
use Phoundation\Databases\Sql\Sql;
use Phoundation\Utils\Json;
use Phoundation\Web\Html\Components\HtmlDataTable;
use Phoundation\Web\Html\Components\Interfaces\HtmlDataTableInterface;
use Phoundation\Web\Html\Html;
use Phoundation\Web\Http\Url;


/**
 * Class MetaList
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class MetaList
{
    /**
     * @var array|null $meta_list
     */
    protected ?array $meta_list = null;


    /**
     * MetaList class constructor
     *
     * @param array|string $meta_list
     */
    public function __construct(array|string $meta_list)
    {
        $this->meta_list = $meta_list;
    }


    /**
     * Returns a new MetaList object
     *
     * @param array|string $meta_list
     * @return MetaList
     */
    public static function new(array|string $meta_list): MetaList
    {
        return new MetaList($meta_list);
    }


    /**
     * Returns a DataTable object
     *
     * @param array|string|null $columns
     * @return HtmlDataTableInterface
     */
    public function getHtmlDataTable(array|string|null $columns = null): HtmlDataTableInterface
    {
        // Create and return the table
        $in     = Sql::in($this->meta_list);
        $source = sql()->list('SELECT    `meta_history`.`id`,
                                               DATE_FORMAT(`meta_history`.`created_on`, "%d-%m-%Y %h:%m:%s") AS `date_time`,
                                               COALESCE(NULLIF(TRIM(CONCAT_WS(" ", `first_names`, `last_names`)), ""), `nickname`, `username`, `email`, "' . tr('System') . '") AS `user`,
                                               `meta_history`.`action`,  
                                               `meta_history`.`source`,  
                                               `meta_history`.`comments`,
                                               `meta_history`.`data`
                                     FROM      `meta_history`          
                                     LEFT JOIN `accounts_users`
                                     ON        `accounts_users`.`id` = `meta_history`.`created_by`
                                     WHERE     `meta_history`.`meta_id` IN (' . implode(', ', array_keys($in)) . ')
                                     ORDER BY  `meta_history`.`created_on` DESC', $in);

        foreach ($source as &$row) {
            $row['data'] = Json::decode($row['data']);

            if (Url::isValid($row['source'])) {
                $row['source'] = '<a href = "' . $row['source'] . '">' . $row['source'] . '</a>';
            }

            if (isset_get($row['data']['to'])) {
                foreach (['to', 'from'] as $section) {
                    unset($row['data'][$section]['id']);
                    unset($row['data'][$section]['created_by']);
                    unset($row['data'][$section]['created_on']);
                    unset($row['data'][$section]['meta_id']);
                    unset($row['data'][$section]['meta_state']);
                    unset($row['data'][$section]['status']);
                }

                foreach ($row['data']['to'] as &$value) {
                    if ($value) {
                        $value = '<span class="success">' . Html::safe($value) . '</span>';
                    }
                }

                unset($value);

                if (isset_get($row['data']['from'])) {
                    foreach ($row['data']['from'] as &$value) {
                        if ($value) {
                            $value = '<span class="danger">' . Html::safe($value) . '</span>';
                        }
                    }

                    unset($value);

                    $row['data'] = '<b>' . tr('From: ') . '</b><br>' . Arrays::implodeWithKeys($row['data']['from'], PHP_EOL, ': ') . '<br><b>' . tr('To: ') . '</b><br>' . Arrays::implodeWithKeys($row['data']['to'], PHP_EOL, ': ');

                } else {
                    $row['data'] = '<b>' . tr('Created with: ') . '</b><br>' . Arrays::implodeWithKeys($row['data']['to'], PHP_EOL, ': ');
                }

                $row['data'] = str_replace(PHP_EOL, '<br>', $row['data']);

            } else {
                $row['data'] = tr('No changes');
            }
        }

        unset($row);

         $table = HtmlDataTable::new()
            ->setId('meta')
            ->setProcessEntities(false)
            ->setSource($source);

         $table->getHeaders()->setSource([
             tr('Date'),
             tr('User'),
             tr('Action'),
             tr('Source'),
             tr('Comments'),
             tr('Data'),
         ]);

        return $table;
    }
}
