<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components;

use Phoundation\Web\Http\Html\Modals\SignInModal;


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
        $this->getModals()->add('sign-in', new SignInModal());
    }
}