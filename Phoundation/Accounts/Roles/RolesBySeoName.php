<?php

/**
 * Class RolesBySeoName
 *
 * Same as Roles class, only the roles are now stored by seo_name key instead of ID
 *
 * @see       DataIterator
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Roles;


class RolesBySeoName extends Roles
{
    /**
     * RolesBySeoName class constructor
     */
    public function __construct()
    {
        $this->keys_are_unique_column = true;
        parent::__construct();
    }
}
