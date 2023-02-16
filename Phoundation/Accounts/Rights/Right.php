<?php

namespace Phoundation\Accounts\Rights;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;


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
        static::$entry_name  = 'right';
        $this->table         = 'accounts_rights';
        $this->unique_column = 'seo_name';

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
            'name'        => 12,
            'description' => 12
        ];

        parent::setKeys();
    }
}