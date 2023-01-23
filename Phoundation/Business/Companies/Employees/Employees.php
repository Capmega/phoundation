<?php

namespace Phoundation\Business\Companies\Employees;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataList\DataList;


/**
 * Class Employees
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Companies
 */
class Employees extends DataList
{
    /**
     * DataList class constructor
     *
     * @param DataEntry|null $parent
     */
    public function __construct(?DataEntry $parent = null)
    {
        $this->entry_class = Employee::class;
        parent::__construct($parent);
    }



    /**
     * @inheritDoc
     */
     protected function load(bool $details = false): static
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