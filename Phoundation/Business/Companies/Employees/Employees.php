<?php

namespace Phoundation\Business\Companies\Employees;

use Phoundation\Data\DataList;


/**
 * Class Employees
 *
 *
 *
 * @see \Phoundation\Data\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Companies
 */
class Employees extends DataList
{
    /**
     * @inheritDoc
     */
    protected function load(): static
    {
        // TODO: Implement load() method.
    }



    /**
     * @inheritDoc
     */
    public function save(): static
    {
        return $this;
    }
}