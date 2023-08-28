<?php

namespace Phoundation\Accounts\Rights\Interfaces;

use Phoundation\Accounts\Roles\Interfaces\RolesInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;


/**
 * Interface RightInterface
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
interface RightInterface extends DataEntryInterface
{
    /**
     * Returns the roles that give this right
     *
     * @return RolesInterface
     */
    public function getRoles(): RolesInterface;
}
