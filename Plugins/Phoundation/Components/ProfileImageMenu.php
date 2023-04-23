<?php

namespace Plugins\Phoundation\Components;

use Phoundation\Web\Http\Html\Components\Menu;


/**
 * ProfileImageMenu class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package PLugins\Phoundation
 */
class ProfileImageMenu extends Menu
{
    public function __construct()
    {
       parent::__construct();

       $this->setSource([
            tr('Profile') => [
                'url'  => '/profile.html',
                'icon' => ''
            ],
            tr('Sign out') => [
                'url'  => '/sign-out.html',
                'icon' => ''
            ],
        ]);
    }
}