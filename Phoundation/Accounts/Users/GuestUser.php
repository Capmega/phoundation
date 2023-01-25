<?php

namespace Phoundation\Accounts\Users;



/**
 * Class GuestUser
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class GuestUser extends User
{
    /**
     * GuestUser class constructor
     *
     * @param string|int|null $identifier
     */
    public function __construct(string|int|null $identifier = null)
    {
        parent::__construct(null);
        $this->id = -1;
        $this->setNickname('Guest');
    }
}