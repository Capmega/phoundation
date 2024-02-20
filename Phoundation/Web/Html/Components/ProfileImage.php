<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Content\Images\Interfaces\ImageInterface;
use Phoundation\Core\Sessions\Session;
use Phoundation\Web\Page;


/**
 * ProfileImage class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class ProfileImage extends ImageMenu
{
    /**
     * ProfileImage class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        // Set up the default image URL
//        $this->setUrl('sign-in');
        $this->setImage(Session::getUser()->getPicture());

        if (Session::getUser()->isGuest()) {
            // This is a guest user, make sure that the profile image shows the sign in modal
            $this->setModalSelector('#signinModal');

        } else {
            $this->setMenu(Page::getMenus()->getMenu('profile_image'));
        }

        parent::__construct($content);
    }


    /**
     * ProfileImage class constructor
     *
     * @param ImageInterface|string|null $image
     * @return ProfileImage
     */
    public function setImage(ImageInterface|string|null $image = null): static
    {
        // Ensure we have a default profile image
        if (!is_object($image)) {
            if (!$image) {
                // Default to default profile image
                $image = 'img/profiles/default.png';
            }
        } elseif (!$image->getPath()) {
            $image->setPath('img/profiles/default.png');
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
                $menu = Menu::new()->setSource([
                    tr('Profile')  => '/my/profile.html',
                    tr('Sign out') => '/sign-out.html'
                ]);
            }
        }

        return parent::setMenu($menu);
    }
}
