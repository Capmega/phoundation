<?php

namespace Phoundation\Web\Http\Html\Components;

use Phoundation\Content\Images\Image;
use Phoundation\Core\Session;
use Phoundation\Web\Http\Html\Template\TemplateMenus;



/**
 * ProfileImage class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class ProfileImage extends ImageMenu
{
    /**
     * ProfileImage class constructor
     */
    public function __construct()
    {
        // Set up the default image URL
//        $this->setUrl(Config::get('web.pages.signin', ''));
        $this->setImage(Session::getUser()->getPicture());

        if (Session::getUser()->isGuest()) {
            // This is a guest user, make sure that the profile image shows the sign in modal
            $this->setModalSelector('#signinModal');
        } else {
            $this->setMenu(TemplateMenus::getProfileImageMenu());
        }

        parent::__construct();
    }



    /**
     * ProfileImage class constructor
     *
     * @param Image|string|null $image
     * @return ProfileImage
     */
    public function setImage(Image|string|null $image = null): static
    {
        // Ensure we have a default profile image
        if (!is_object($image)) {
            if (!$image) {
                // Default to default profile image
                $image = 'img/profiles/default.png';
            }
        } elseif (!$image->getFile()) {
            $image->setFile('img/profiles/default.png');
        }

        return parent::setImage($image);
    }



    /**
     * Set the menu for this profile image
     *
     * @param Menu|null $menu
     * @return static
     */
    public function setMenu(Menu|null $menu): static
    {
        if (Session::getUser()->isGuest()) {
            // Don't show menu
            $menu = null;
        } else {
            // Default image menu
            if (!$menu) {
                $menu = DropDownMenu::new([
                    tr('Profile')  => '/profile.html',
                    tr('Sign out') => '/sign-out.html'
                ]);
            }
        }

        return parent::setMenu($menu);
    }
}