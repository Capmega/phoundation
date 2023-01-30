<?php

namespace Plugins\Phoundation;

use Phoundation\Web\Http\Html\Components\Menu;
use Phoundation\Web\Page;
use Plugins\Phoundation\Components\ProfileImageMenu;


/**
 * Class Plugin
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\Phoundation
 */
class Plugin extends \Phoundation\Core\Plugins\Plugin
{
    /**
     * @inheritDoc
     */
    public function install(): void
    {
        $this->setDescription(tr('This is the default Phoundation plugin'));
        $this->save();
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



    /**
     * @inheritDoc
     */
    public function uninstall(): void
    {
        // TODO: Implement uninstall() method.
    }



    /**
     * @inheritDoc
     */
    public function disable(): void
    {
        // TODO: Implement disable() method.
    }



    /**
     * @inheritDoc
     */
    public function enable(): void
    {
        // TODO: Implement enable() method.
    }
}