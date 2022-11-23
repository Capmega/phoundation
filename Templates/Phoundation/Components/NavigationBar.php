<?php

namespace Templates\Phoundation\Components;

use Plugins\Mdb\Components\NavBar;
use Templates\Phoundation\Modals\SignInModal;



/**
 * Phoundation Template NavigationBar class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\Phoundation
 */
class NavigationBar extends NavBar
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