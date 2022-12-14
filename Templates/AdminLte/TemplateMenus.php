<?php

namespace Templates\AdminLte;



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
    public static function getSecondaryMenu(): ?array
    {
        return parent::getSecondaryMenu();
    }

    public static function getPrimaryMenu(): ?array
    {
        return parent::getPrimaryMenu();
    }

    public static function getProfileImageMenu(): ?array
    {
        return parent::getProfileImageMenu();
    }
}