<?php

namespace Phoundation\Accounts;

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
     protected function load(bool $details = false): static
    {
        return $this;
    }

    public function save(): static
    {
        return $this;
    }
}