<?php

declare(strict_types=1);

namespace Plugins\Phoundation\Library;

use Phoundation\Web\Html\Components\Menus\Menu;
use Phoundation\Web\Page;
use Plugins\Phoundation\Components\ProfileImageMenu;


/**
 * Class Plugin
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
        Page::getMenusObject()->setMenus([
            'primary'       => Menu::new()->append(\Plugins\Phoundation\Components\Menu::new()),
            'profile_image' => Menu::new()->append(ProfileImageMenu::new())
        ]);
    }
}