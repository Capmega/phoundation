<?php

namespace Phoundation\Web\Http\Html\Components;

use Phoundation\Content\Images\Image;
use Phoundation\Core\Config;
use Phoundation\Core\Session;
use Phoundation\Web\Http\Url;



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
        $this->setUrl(Config::get('web.pages.signin', ''));
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
     * @return static
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



    /**
     * Render the ProfileImage HTML
     *
     * @return string
     */
    public function render(): string
    {
        // TODO: Implement render() method.
    }
}