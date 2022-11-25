<?php

namespace Phoundation\Business\Companies\Employees;

use Phoundation\Data\DataEntry;



/**
 * Class Employee
 *
 *
 *
 * @see \Phoundation\Data\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Companies
 */
class Employee extends DataEntry
{

    /**
     * @inheritDoc
     */
    public function save(): static
    {
        // TODO: Implement save() method.
    }

    /**
     * @inheritDoc
     */
    protected function setKeys(): void
    {
        // TODO: Implement setKeys() method.
    }

    /**
     * @inheritDoc
     */
    protected function load(int|string $identifier): void
    {
        // TODO: Implement load() method.
    }
}