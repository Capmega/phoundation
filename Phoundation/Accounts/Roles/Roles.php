<?php

namespace Phoundation\Accounts\Roles;

use Phoundation\Core\Arrays;
use Phoundation\Data\DataEntry;
use Phoundation\Data\DataList;



/**
 * Class Roles
 *
 *
 *
 * @see \Phoundation\Data\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Roles extends DataList
{
    /**
     * DataList class constructor
     *
     * @param DataEntry|null $parent
     */
    public function __construct(?DataEntry $parent = null)
    {
        $this->entry_class = Role::class;
        parent::__construct($parent);
    }



    /**
     * Load the data for this roles list
     *
     * @param string|null $columns
     * @return static
     */
    public function load(?string $columns = null): static
    {
        // Validate columns
        $columns = Arrays::force($columns ?? 'id,status,name,description,roles');
        $rights  = false;

        foreach ($columns as &$column) {
            switch ($column) {
                case 'id':
                    // no-break
                case 'status':
                    // no-break
                case 'name':
                    // no-break
                case 'description':
                    break;

                case 'roles':
                    // Select 0 as roles so that we will have the column in the right position
                    $column = '0 AS `roles`';
                    $rights = true;
                    break;
            }
        }

        // Load the data
        $this->list = sql()->list('SELECT ' . implode(',', $columns) . ' FROM `accounts_roles`');

        if ($rights) {
            foreach ($this->list as $key => &$value) {
                $rights = sql()->list('SELECT `accounts_rights`.`name` 
                                             FROM   `accounts_rights` 
                                             JOIN   `accounts_roles_rights`
                                             ON     `accounts_roles_rights`.`roles_id`  = :id
                                             AND    `accounts_roles_rights`.`rights_id` = `accounts_rights`.`id`', [
                                                 ':id' => $key
                ]);

                $value['roles'] = implode(', ', $rights);
            }
        }

        return $this;
    }



    /**
     * Save this roles list
     *
     * @return static
     */
    public function save(): static
    {
        return $this;
    }
}