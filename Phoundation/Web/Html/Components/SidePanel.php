<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Web\Html\Components\Modals\SignInModal;


/**
 * SidePanel class
 *
 * 
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
        $this->getModals()->addModal('sign-in', new SignInModal());
    }
}