<?php

namespace Phoundation\Business\Companies\Branches;

use Phoundation\Data\DataEntry;
use Phoundation\Data\DataList;



/**
 * Class Branches
 *
 *
 *
 * @see \Phoundation\Data\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Companies
 */
class Branches extends DataList
{
    /**
     * DataList class constructor
     *
     * @param DataEntry|null $parent
     */
    public function __construct(?DataEntry $parent = null)
    {
        $this->entry_class = Branch::class;
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
        // TODO: Implement save() method.
    }
}