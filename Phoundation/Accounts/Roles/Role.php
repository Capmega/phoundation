<?php

namespace Phoundation\Accounts\Roles;

use Phoundation\Data\DataEntry;
use Phoundation\Data\DataEntryNameDescription;



/**
 * Class Role
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Role extends DataEntry
{
    use DataEntryNameDescription;



    /**
     * Role class constructor
     *
     * @param int|string|null $identifier
     */
    public function __construct(int|string|null $identifier = null)
    {
        self::$entry_name = 'role';
        $this->table      = 'accounts_roles';

        parent::__construct($identifier);
    }



    /**
     * Sets the available data keys for the Role class
     *
     * @return void
     */
    protected function setKeys(): void
    {
        $this->keys = [
            'id',
            'created_by',
            'created_on',
            'meta_id',
            'status',
            'name',
            'seo_name',
            'description'
        ];
    }
}