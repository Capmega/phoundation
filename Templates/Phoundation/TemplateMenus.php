<?php

namespace Templates\Phoundation;



/**
 * TemplateMenus class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\Phoundation
 */
class TemplateMenus extends \Phoundation\Web\Http\Html\Template\TemplateMenus
{
    public function getSidebarMenu(): ?array
    {
        return parent::getSidebarMenu();
    }

    public function getNavigationMenu(): ?array
    {
        return parent::getNavigationMenu();
    }

    public function getProfileImageMenu(): ?array
    {
        return parent::getProfileImageMenu();
    }
}