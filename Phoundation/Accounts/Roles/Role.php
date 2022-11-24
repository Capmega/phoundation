<?php

namespace Phoundation\Accounts\Roles;

use Phoundation\Data\DataEntry;



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
    /**
     *
     *
     * @return static
     */
    public function save(): static
    {
        // TODO: Implement save() method.
        return $this;
    }



    /**
     *
     *
     * @param int $identifier
     * @return void
     */
    protected function load(int $identifier): void
    {
        // TODO: Implement load() method.
    }



    /**
     *
     *
     * @return void
     */
    protected function setKeys(): void
    {
        // TODO: Implement setKeys() method.
    }
}