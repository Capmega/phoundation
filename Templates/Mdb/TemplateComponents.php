<?php

 namespace Templates\Mdb;

use Phoundation\Core\Config;
use Phoundation\Web\Http\Url;
use Plugins\Mdb\Components\ProfileImage;
use Templates\Mdb\Components\TopBar;



/**
 * TemplateComponents class
 *
 * This class contains various MDB template components
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\Mdb
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
        // TODO: Implement buildSidebarMenu() method.
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
            ->setMenu($navigation_menu)
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