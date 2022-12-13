<?php

namespace Phoundation\Web\Http\Html\Template;

use Phoundation\Web\Http\Html\Components\ImageMenu;



/**
 * TemplateComponents class
 *
 * This is an abstract class for template component classes to extend
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
abstract class TemplateComponents
{
    /**
     * Returns the sidebar menu
     *
     * @return string|null
     */
    public abstract function buildSidebar(): ?string;



    /**
     * Build the page top navigation bar
     *
     * @param array|null $navigation_menu
     * @return string|null
     */
    public abstract function buildTopBar(?array $navigation_menu): ?string;



    /**
     * Builds and returns a top navbar top menu
     *
     * @return string|null
     */
    public abstract function getNavigationMenu(): ?string;



    /**
     * Returns a profile image object
     *
     * @return ImageMenu
     */
    public abstract function profileImage(): ImageMenu;



    /**
     * Builds and returns a footer bar
     *
     * @return string|null
     */
    public abstract function buildFooter(): ?string;
}