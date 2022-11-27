<?php

namespace Phoundation\Accounts\Rights;

use Phoundation\Data\DataList;



/**
 * Class Rights
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Rights extends DataList
{
    /**
     * Load the data for this rights list
     *
     * @param bool $details
     * @return static
     */
    public function load(bool $details = false): static
    {
        $this->list = sql()->list('SELECT * FROM `accounts_rights`');
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