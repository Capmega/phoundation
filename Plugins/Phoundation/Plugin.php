<?php

declare(strict_types=1);


namespace Plugins\Phoundation;

use Phoundation\Web\Html\Components\Menu;
use Phoundation\Web\Page;
use Plugins\Phoundation\Components\ProfileImageMenu;


/**
 * Class Plugin
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\Phoundation
 */
class Plugin extends \Phoundation\Core\Plugins\Plugin
{
    /**
     * Returns the plugin description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return tr('This is the default Phoundation plugin');
    }


    /**
     * @return void
     */
    public static function start(): void
    {
        // TODO Use hooks after startup!
        Page::getMenus()->setMenus([
            'primary'       => Menu::new()->appendMenu(Components\Menu::new()),
            'profile_image' => Menu::new()->appendMenu(ProfileImageMenu::new())
        ]);
    }
}