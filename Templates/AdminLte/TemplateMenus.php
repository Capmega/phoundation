<?php

namespace Templates\AdminLte;

use Templates\AdminLte\Components\Menu;



/**
 * TemplateMenus class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class TemplateMenus extends \Phoundation\Web\Http\Html\Template\TemplateMenus
{
    public function __construct()
    {
        self::$menu_class = Menu::class;
    }
}