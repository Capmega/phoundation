<?php

namespace Plugins\Mdb;

use Phoundation\Content\Images\Image;
use Phoundation\Core\Config;
use Phoundation\Core\Session;
use Phoundation\Web\Http\Url;



/**
 * MDB Plugin ProfileImage class
 *
 * This class is an example template for your website
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\Mdb
 */
class ProfileImage extends ImageMenu
{
    /**
     * ProfileImage class constructor
     */
    public function __construct()
    {
        // Set up the default image URL
        $this->setUrl(Config::get('web.pages.signin', 'sign-in.html'));
        $this->setModalSelector('#signinModal');

        parent::__construct();
    }



    /**
     * ProfileImage class constructor
     */
    public function setImage(Image|string|null $image = null): static
    {
        // Ensure we have a default profile image
        if (!is_object($image)) {
            if (!$image) {
                // Default to default profile image
                $image = 'profiles/default.png';
            }
        } elseif (!$image->getFile()) {
            $image->setFile('profiles/default.png');
        }

        return parent::setImage($image);
    }



    /**
     * Set the menu for this profile image
     *
     * @param array|null $menu
     * @return $this
     */
    public function setMenu(?array $menu): static
    {
        if (Session::getUser()->isGuest()) {
            // Don't show menu
            $menu = null;
        } else {
            // Default image menu
            if (!$menu) {
                $menu = [
                    tr('Profile')  => Url::build('/profile')->www(),
                    tr('Sign out') => Url::build('/signout')->www()
                ];
            }
        }

        return parent::setMenu($menu);
    }
}