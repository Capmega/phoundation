<?php

namespace Phoundation\Web\Http\Html;



/**
 * Class Table
 *
 * This class can create various HTML tables
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
Class Table extends ResourceElement
{
    /**
     * Table constructor
     */
    public function __construct()
    {
        parent::__construct('table');
    }



    /**
     * Render the table
     *
     * @return string
     */
    public function render(): string
    {
        return $this->renderHeaders() . $this->renderHeaders() . '</table>';
    }



    /**
     * Render the table body
     *
     * @return string
     */
    public function renderBody(): string
    {
        $return = '<table>';

        return $return;
    }



    /**
     * Render the table body
     *
     * @return string
     */
    protected function renderHeaders(): string
    {
        $return = '<table>';

        return $return;
    }
}