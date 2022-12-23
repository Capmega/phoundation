<?php

namespace Phoundation\Accounts\Roles;

use Phoundation\Accounts\Rights\Rights;
use Phoundation\Accounts\Users\Users;
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
     * Add the specified rights to this role
     *
     * @return Rights
     */
    public function rights(): Rights
    {
        if (!$this->list) {
            $this->list = new Rights($this);
        }

        return $this->list;
    }



    /**
     * Returns the users that are linked to this role
     *
     * @return Users
     */
    public function users(): Users
    {
        return new Users($this);
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