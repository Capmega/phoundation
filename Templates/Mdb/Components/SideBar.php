<?php

 namespace Templates\Mdb\Components;

use Templates\Mdb\Modals\SignInModal;



/**
 * Mdb Template SidePanel class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\Mdb
 */
class SidePanel extends \Templates\Mdb\Components\SidePanel
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