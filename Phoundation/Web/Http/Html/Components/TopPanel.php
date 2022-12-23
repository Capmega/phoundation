<?php

namespace Phoundation\Web\Http\Html\Components;



/**
 * AdminLte Plugin TopPanel class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class TopPanel extends Panel
{
    /**
     * TopPanel constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->menu = new TopMenu();
    }



    /**
     * Renders and returns the top panel
     *
     * @return string|null
     */
    public function render(): ?string
    {
       return '';
    }
}