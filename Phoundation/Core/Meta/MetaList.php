<?php

namespace Phoundation\Core\Meta;



use Phoundation\Databases\Sql\Sql;
use Phoundation\Web\Http\Html\Components\DataTable;

/**
 * Class MetaList
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * @return DataTable
     */
    public function getHtmlDataTable(): DataTable
    {
        // Create and return the table
        $in = Sql::in($this->meta_list);

        return DataTable::new()
            ->setId('meta')
            ->setSourceQuery(' SELECT    DATE_FORMAT(`meta_history`.`created_on`, "%d-%m-%Y") AS `date`,
                                                    COALESCE(NULLIF(TRIM(CONCAT_WS(" ", `first_names`, `last_names`)), ""), `nickname`, `username`, `email`) AS `user`,
                                                    `meta_history`.`action`,  
                                                    `meta_history`.`source`,
                                                    `meta_history`.`comments`,
                                                    `meta_history`.`data`
                                          FROM      `meta_history`          
                                          LEFT JOIN `accounts_users`
                                          ON        `accounts_users`.`id` = `meta_history`.`created_by`
                                          WHERE     `meta_history`.`meta_id` IN (' . implode(', ', array_keys($in)) . ')
                                          ORDER BY  `meta_history`.`created_on`', $in);

    }
}