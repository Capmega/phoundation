<?php

namespace Phoundation\Accounts\Rights;



/**
 * Class RoleRights
 *
 * This class is a Rights list object with rights limited to the specified role
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class RoleRights extends Rights
{
    /**
     * DataList class constructor
     *
     * @param Right|null $parent
     */
    public function __construct(?Right $parent = null)
    {
        parent::__construct($parent);
    }



    /**
     * Load the data for this rights list
     *
     * @return static
     */
    public function load(): static
    {
        $this->list = sql()->list('SELECT     `accounts_rights`.* 
                                         FROM       `accounts_rights` 
                                         RIGHT JOIN `accounts_`');
        return $this;
    }



    /**
     * Save this rights list
     *
     * @return static
     */
    public function save(): static
    {

        return $this;
    }
}