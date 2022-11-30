<?php

namespace Phoundation\Accounts\Users;

use Phoundation\Data\DataEntry;
use Phoundation\Data\DataList;
use Phoundation\Databases\Sql\QueryBuilder;
use Phoundation\Exception\NotSupportedException;



/**
 * Class Accounts
 *
 *
 *
 * @see \Phoundation\Data\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Users extends DataList
{
    /**
     * DataList class constructor
     *
     * @param DataEntry|null $parent
     */
    public function __construct(?DataEntry $parent = null)
    {
        $this->entry_class = User::class;
        parent::__construct($parent);
    }


    /**
     * Load the data for this users list
     *
     * @param string|null $columns
     * @return static
     */
     protected function load(?string $columns = '*'): static
    {
        $builder = new QueryBuilder();
        $builder->addSelect('SELECT `accounts_users`.' . $columns);
        $builder->addFrom('FROM `accounts_users`');

        foreach ($this->filters as $key => $value){
            switch ($key) {
                case 'roles':
                    $builder->addJoin(' JOIN `accounts_roles` ON `accounts_roles`.`name` ' . $builder->compareQuery('role', $value) . ' JOIN `accounts_users_roles` ON `accounts_users_roles`.`roles_id` = `accounts_roles`.`id` AND `accounts_users_roles`.`users_id` = `accounts_users`.`id`');
                    break;
            }
        }

        $this->list = sql()->list($builder->getQuery(), $builder->getExecute());
        return $this;
    }



    /**
     * Save this users list
     *
     * @note Currently not supported, what would be there to save?
     * @return $this
     */
    public function save(): static
    {
        throw new NotSupportedException('General Users lists cannot be saved');
        // TODO: Implement save() method.
    }
}