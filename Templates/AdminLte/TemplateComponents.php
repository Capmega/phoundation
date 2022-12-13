<?php

namespace Templates\AdminLte;

use Phoundation\Core\Config;
use Phoundation\Web\Http\Url;
use Plugins\AdminLte\Components\BreadCrumbs;
use Plugins\AdminLte\Components\ProfileImage;
use Plugins\AdminLte\Components\SideBar;
use Templates\AdminLte\Components\TopBar;



/**
 * TemplateComponents class
 *
 * This class contains various AdminLte template components
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\Phoundation
 */
class TemplateComponents extends \Phoundation\Web\Http\Html\Template\TemplateComponents
{
    /**
     * Builds and returns a sidebar menu
     *
     * @return string|null
     */
    public function buildSidebar(): ?string
    {
        return SideBar::new()->render();
    }



    /**
     * Builds and returns a navigation bar
     *
     * @param array|null $navigation_menu
     * @return string|null
     */
    public function buildTopBar(?array $navigation_menu): ?string
    {
        // Set up the navigation bar
        $navigation_bar = TopBar::new();
        $navigation_bar
            ->setBreadCrumbs(BreadCrumbs::new($this->bread_crumbs))
            ->getSignInModal()
            ->getForm()
            ->setId('form-signin')
            ->setMethod('post')
            ->setAction(Url::build(Config::get('web.pages.signin', '/system/sign-in.html'))->ajax());

        return $navigation_bar->render();
    }



    /**
     * Builds and returns a navigation menu
     *
     * @return string|null
     */
    public function getNavigationMenu(): ?string
    {
        // TODO: Implement getNavigationMenu() method.
    }



    /**
     * Builds and returns a profile image
     *
     * @return ProfileImage
     */
    public function profileImage(): ProfileImage
    {
        return ProfileImage::new();
    }



    /**
     * Build footer
     *
     * @return string|null
     */
   public function buildFooter(): ?string
    {
        // TODO: Implement buildFooter() method.
    }
}