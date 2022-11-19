<?php

namespace Plugins\Mdb\Components;

use Phoundation\Web\Http\Html\Elements\Section;



/**
 * MDB Plugin Table class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\Mdb
 */
class Table extends \Phoundation\Web\Http\Html\Elements\Table
{
    /**
     * Sets whether the table is responsive or not
     *
     * @var bool
     */
    protected bool $responsive = true;



    /**
     * Table class constructor
     */
    public function __construct()
    {
        $this->addClass('table');
        parent::__construct();
    }



    /**
     * Returns if the table is responsive or not
     *
     * @return bool
     */
    public function getResponsive(): bool
    {
        return $this->responsive;
    }



    /**
     * Sets if the table is responsive or not
     *
     * @param bool $responsive
     * @return $this
     */
    public function setResponsive(bool $responsive): static
    {
        $this->responsive = $responsive;
        return $this;
    }



    /**
     * Render the MDB table
     *
     * @return string
     */
    public function render(): string
    {
        // Render the table
        $table = parent::render();
showdie(Section::new()
    ->addClass($this->responsive ? 'table-responsive' : null)
    ->setContent($table)
    ->render());

        // Render the section around it
        return Section::new()
            ->addClass($this->responsive ? 'table-responsive' : null)
            ->setContent($table)
            ->render();
    }
}