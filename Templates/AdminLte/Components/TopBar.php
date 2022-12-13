<?php

namespace Templates\AdminLte\Components;

use Templates\AdminLte\Modals\SignInModal;



/**
 * AdminLte Template TopBar class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class TopBar extends \Plugins\AdminLte\Components\TopBar
{
    /**
     * Sign in modal for this navigation bar
     */
    public function __construct()
    {
        parent::__construct();
        $this->sign_in_modal = new SignInModal();
    }
}