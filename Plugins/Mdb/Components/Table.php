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
     * @var bool $responsive
     */
    protected bool $responsive = true;

    /**
     * Sets whether the table is full width or not
     *
     * @var bool $full_width
     */
    protected bool $full_width = true;



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
     * Returns if the table is full width or not
     *
     * @return bool
     */
    public function getFullWidth(): bool
    {
        return $this->full_width;
    }



    /**
     * Sets if the table is full width or not
     *
     * @param bool $full_width
     * @return $this
     */
    public function setFullWidth(bool $full_width): static
    {
        $this->full_width = $full_width;
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

        // Render the section around it
        $return = Section::new()
            ->addClass($this->full_width ? 'w-100' : null)
            ->addClass($this->responsive ? 'table-responsive' : null)
            ->setContent($table)
            ->render();

        // Render the section around it
        $return = Section::new()
            ->addClass('bg-white border rounded-5')
            ->setContent($return)
            ->render();

        // Render the section around it
        $return = Section::new()
            ->addClass('pb-4')
            ->setContent($return)
            ->render();

        return $return;
    }
}