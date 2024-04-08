<?php

declare(strict_types=1);

namespace Phoundation\Core\Meta;

use Phoundation\Databases\Sql\SqlQueries;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Json;
use Phoundation\Web\Html\Components\Tables\HtmlDataTable;
use Phoundation\Web\Html\Components\Tables\Interfaces\HtmlDataTableInterface;
use Phoundation\Web\Html\Enums\EnumTableIdColumn;
use Phoundation\Web\Html\Html;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Http\UrlBuilder;

/**
 * Class MetaList
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
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
     * Returns a DataTable object
     *
     * @param array|string|null $columns
     *
     * @return HtmlDataTableInterface
     */
    public function getHtmlDataTable(array|string|null $columns = null): HtmlDataTableInterface
    {
        // Create and return the table
        $in     = SqlQueries::in($this->meta_list);
        $source = sql()->list('SELECT    `meta_history`.`id`,
                                               `meta_history`.`created_by`,
                                               DATE_FORMAT(`meta_history`.`created_on`, "%Y-%m-%d %h:%m:%s") AS `date_time`,
                                               COALESCE(NULLIF(TRIM(CONCAT_WS(" ", `first_names`, `last_names`)), ""), `nickname`, `username`, `email`, "' . tr('System') . '") AS `user`,
                                               `meta_history`.`action`,  
                                               `meta_history`.`source`,  
                                               `meta_history`.`data`,
                                               `meta_history`.`comments`
                                     FROM      `meta_history`          
                                     LEFT JOIN `accounts_users`
                                     ON        `accounts_users`.`id` = `meta_history`.`created_by`
                                     WHERE     `meta_history`.`meta_id` IN (' . implode(', ', array_keys($in)) . ')
                                     ORDER BY  `meta_history`.`created_on` DESC', $in);
        foreach ($source as &$row) {
            if ($row['created_by']) {
                $row['user'] = '<a href="' . UrlBuilder::getWww('profiles/profile+' . $row['created_by'] . '.html') . '">' . $row['user'] . '</a>';
            }
            $row['data'] = Json::decode($row['data']);
            unset($row['created_by']);
            if (Url::isValid($row['source'])) {
                $row['source'] = '<a href = "' . $row['source'] . '">' . $row['source'] . '</a>';
            }
            if (isset_get($row['data']['to'])) {
                foreach ([
                    'to',
                    'from',
                ] as $section) {
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
                              ->setCheckboxSelectors(EnumTableIdColumn::visible)
                              ->setJsDateFormat('YYYY-MM-DD HH:mm:ss')
                              ->setOrder([0 => 'desc'])
                              ->setProcessEntities(false)
                              ->setSource($source);
        $table->getHeaders()
              ->setSource([
                  tr('Date'),
                  tr('User'),
                  tr('Action'),
                  tr('Source'),
                  tr('Changes'),
                  tr('Comments'),
              ]);

        return $table;
    }


    /**
     * Returns a new MetaList object
     *
     * @param array|string $meta_list
     *
     * @return MetaList
     */
    public static function new(array|string $meta_list): MetaList
    {
        return new MetaList($meta_list);
    }
}
