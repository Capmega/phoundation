<?php

/**
 * ProfileImage class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets;

use Phoundation\Content\Images\Interfaces\ImageFileInterface;
use Phoundation\Core\Sessions\Session;
use Phoundation\Web\Html\Components\Widgets\Menus\Interfaces\MenuInterface;
use Phoundation\Web\Html\Components\Widgets\Menus\Menu;
use Phoundation\Web\Requests\Request;


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
        $this->setImage(Session::getUserObject()
                               ->getImageFileObject());
        if (
            Session::getUserObject()
                   ->isGuest()
        ) {
            // This is a guest user, make sure that the profile image shows the sign in modal
            $this->setModalSelector('#signinModal');

        } else {
            $this->setMenu(Request::getMenusObject()
                                  ->getMenu('profile_image'));
        }
        parent::__construct($content);
    }


    /**
     * ProfileImage class constructor
     *
     * @param ImageFileInterface|string|null $image
     *
     * @return ProfileImage
     */
    public function setImage(ImageFileInterface|string|null $image = null): static
    {
        // Ensure we have a default profile image
        if (!is_object($image)) {
            if (!$image) {
                // Default to default profile image
                $image = 'img/profiles/default.png';
            }

        } elseif (!$image->getSource()) {
            $image->setSource('img/profiles/default.png');
        }

        return parent::setImage($image);
    }


    /**
     * Set the menu for this profile image
     *
     * @param Menu|null $menu
     *
     * @return static
     */
    public function setMenu(?MenuInterface $menu): static
    {
        if (
            Session::getUserObject()
                   ->isGuest()
        ) {
            // Don't show menu
            $menu = null;
        } else {
            // Default image menu
            if (!$menu) {
                $menu = Menu::new()
                            ->setSource([
                                tr('Profile') => '/my/profile.html',
                                tr('Sign out') => '/sign-out.html',
                            ]);
            }
        }

        return parent::setMenu($menu);
    }
}
