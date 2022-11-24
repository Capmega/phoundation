<?php

namespace Phoundation\Accounts\Roles;

use Phoundation\Data\DataEntry;



/**
 * Class User
 *
 * This is the default user class.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Role extends DataEntry
{
    /**
     *
     *
     * @return void
     */
    function setKeys(): void
    {
        // TODO: Implement setKeys() method.
    }



    /**
     *
     *
     * @param int $identifier
     * @return void
     */
    function load(int $identifier): void
    {
        // TODO: Implement load() method.
    }



    /**
     *
     *
     * @return static
     */
    function save(): static
    {
        // TODO: Implement save() method.
        return $this;
    }
}