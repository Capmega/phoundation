<?php

namespace Phoundation\Accounts\Roles;

use Phoundation\Data\DataList;



/**
 * Class Roles
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class Roles extends DataList
{
    /**
     * Load the data for this roles list
     *
     * @return static
     */
    public function load(): static
    {

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