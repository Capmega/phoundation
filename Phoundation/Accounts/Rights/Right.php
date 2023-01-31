<?php

namespace Phoundation\Accounts\Rights;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\DataEntryNameDescription;


/**
 * Class Right
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Right extends DataEntry
{
    use DataEntryNameDescription;



    /**
     * Right class constructor
     *
     * @param int|string|null $identifier
     */
    public function __construct(int|string|null $identifier = null)
    {
        static::$entry_name = 'right';
        $this->table      = 'accounts_rights';

        parent::__construct($identifier);
    }



    /**
     * Sets the available data keys for the Right class
     *
     * @return void
     */
    protected function setKeys(): void
    {
        $this->keys = [
            'id' => [
                'disabled' => true,
                'type'     => 'numeric',
                'label'    => tr('Database ID')
            ],
            'created_on' => [
                'disabled' => true,
                'type'     => 'date',
                'label'    => tr('Created on')
            ],
            'created_by' => [
                'element'  => 'input',
                'disabled' => true,
                'source'   => 'SELECT IFNULL(`username`, `email`) AS `username` FROM `accounts_users` WHERE `id` = :id',
                'execute'  => 'id',
                'label'    => tr('Created by')
            ],
            'meta_id' => [
                'disabled' => true,
                'element'  => null, //Meta::new()->getHtmlTable(), // TODO implement
                'label'    => tr('Meta information')
            ],
            'status' => [
                'disabled' => true,
                'default'  => tr('Ok'),
                'label'    => tr('Status')
            ],
            'name' => [
                'label'    => tr('Username')
            ],
            'seo_name' => [
                'display' => false
            ],
            'description' => [
                'element' => 'text',
                'label'   => tr('Description'),
            ]
        ];

        $this->keys_display = [
            'id'          => 12,
            'created_by'  => 6,
            'created_on'  => 6,
            'meta_id'     => 6,
            'status'      => 6,
            'name'        => 12,
            'description' => 12
        ];
    }
}