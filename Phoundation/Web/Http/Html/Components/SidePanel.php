<?php

namespace Phoundation\Web\Http\Html\Components;

use Phoundation\Web\Http\Html\Modals\SignInModal;



/**
 * AdminLte Plugin SidePanel class
 *
 * 
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class SidePanel extends Panel
{
    /**
     * SidePanel class constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->getModals()->add('sign-in', new SignInModal());
    }



    /**
     * Renders and returns the sidebar
     *
     * @return string|null
     */
    public function render(): ?string
    {
       return '';
    }
}