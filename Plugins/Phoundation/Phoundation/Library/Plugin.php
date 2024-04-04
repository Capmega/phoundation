<?php

/**
 * Class Plugin
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Plugins\Phoundation
 */

declare(strict_types=1);

namespace Plugins\Phoundation\Phoundation\Library;

use Phoundation\Web\Html\Components\Widgets\Menus\Menu;
use Phoundation\Web\Requests\Request;
use Plugins\Phoundation\Phoundation\Components\ProfileImageMenu;

class Plugin extends \Phoundation\Core\Plugins\Plugin
{
    /**
     * @return void
     */
    public static function start(): void
    {
        // TODO Use hooks after startup!
        Request::getMenusObject()->setMenus([
                                                'primary'       => Menu::new()->appendSource(\Plugins\Phoundation\Phoundation\Components\Menu::new()),
                                                'profile_image' => Menu::new()->appendSource(ProfileImageMenu::new()),
                                            ]);
    }

    /**
     * Returns the plugin description
     *
     * @return string
     */
    public function getDescription(): ?string
    {
        return tr('This is the default Phoundation plugin');
    }
}
