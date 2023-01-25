<?php

namespace Phoundation\Developer\SlowPages;

use Phoundation\Accounts\Roles\Role;
use Phoundation\Accounts\Users\User;
use Phoundation\Developer\Incidents\Incidents;



/**
 * SlowPages class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class SlowPages extends Incidents
{
    /**
     * Users class constructor
     *
     * @param Role|User|null $parent
     * @param string|null $id_column
     */
    public function __construct(Role|User|null $parent = null, ?string $id_column = null)
    {
        $this->entry_class = SlowPage::class;
        $this->table_name  = 'developer_slow_pages';

        $this->setHtmlQuery('SELECT   `id`, `created_on`, `status`, `title` 
                                   FROM     `accounts_users` 
                                   WHERE    `type` = "slow_page" AND `status` IS NULL 
                                   ORDER BY `created_on`');
        parent::__construct($parent, $id_column);
    }
}