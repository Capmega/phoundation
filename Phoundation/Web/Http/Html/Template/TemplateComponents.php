<?php

namespace Phoundation\Web\Http\Html\Template;

use Phoundation\Web\Http\Html\Html;
use Phoundation\Web\WebPage;



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
    public abstract function buildSidebarMenu(): ?string;



    /**
     * Build the page top navigation bar
     *
     * @param array|null $navigation_menu
     * @return string|null
     */
    public abstract function buildNavigationBar(?array $navigation_menu): ?string;



    /**
     * Builds and returns a top navbar top menu
     *
     * @return string|null
     */
    public abstract function getNavigationMenu(): ?string;



    /**
     * Builds and returns a profile image button
     *
     * @param array|null $menu
     * @return string|null
     */
    public abstract function buildProfileImage(?array $menu = null): ?string;



    /**
     * Builds and returns a footer bar
     *
     * @return string|null
     */
    public abstract function buildFooter(): ?string;
}