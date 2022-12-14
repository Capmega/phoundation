<?php

namespace Templates\AdminLte\Components;

use Templates\AdminLte\Modals\SignInModal;



/**
 * AdminLte Template SidePanel class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class SidePanel extends \Plugins\AdminLte\Components\SidePanel
{
    /**
     * SidePanel class constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->getModals()->add('sign-in', new SignInModal());
    }
}