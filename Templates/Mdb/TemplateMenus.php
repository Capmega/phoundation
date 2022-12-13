<?php

 namespace Templates\Mdb;



/**
 * TemplateMenus class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\Mdb
 */
class TemplateMenus extends \Phoundation\Web\Http\Html\Template\TemplateMenus
{
    public static function getSidebarMenu(): ?array
    {
        return parent::getSidebarMenu();
    }

    public static function getNavigationMenu(): ?array
    {
        return parent::getNavigationMenu();
    }

    public static function getProfileImageMenu(): ?array
    {
        return parent::getProfileImageMenu();
    }
}