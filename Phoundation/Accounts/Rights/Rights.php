<?php

namespace Phoundation\Accounts\Rights;

use Phoundation\Data\DataEntry;
use Phoundation\Data\DataList;



/**
 * Class Rights
 *
 *
 *
 * @see \Phoundation\Data\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Rights extends DataList
{
    /**
     * DataList class constructor
     *
     * @param DataEntry|null $parent
     */
    public function __construct(?DataEntry $parent = null)
    {
        $this->entry_class = Right::class;
        parent::__construct($parent);
    }



    /**
     * Load the data for this rights list
     *
     * @param string|null $columns
     * @return static
     */
    public function load(?string $columns = null): static
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