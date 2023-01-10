<?php

namespace Templates\Mdb\Components;

use Phoundation\Content\Images\Image;
use Phoundation\Core\Session;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Components\Menu;
use Templates\Mdb\TemplateMenus;



/**
 * MDB Plugin ProfileImage class
 *
 * 
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\Mdb
 */
class ProfileImage extends \Phoundation\Web\Http\Html\Components\ProfileImage
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



    /**
     * Renders and returns the image menu block HTML
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if (!isset($this->image)) {
            throw new OutOfBoundsException(tr('Cannot render ImageMenu object HTML, no image specified'));
        }
//.        <button type="button" class="btn btn-primary" data-mdb-toggle="modal" data-mdb-target="#exampleModal" style=""> Launch demo modal </button>

        $this->render = ' <div class="dropdown image-menu">
                    <a
                      class="' . ($this->menu ? 'dropdown-toggle ' : '') . 'd-flex align-items-center hidden-arrow"
                      href="' . ($this->menu ? '#' : $this->url) . '"
                      id="navbarDropdownMenuAvatar"
                      ' . ($this->menu ? 'role="button" data-mdb-toggle="dropdown"' : ($this->modal_selector ? 'data-mdb-toggle="modal" data-mdb-target="' . $this->modal_selector . '"' : null)) . '                    
                      aria-expanded="false"
                    >';

        $this->render .= $this->image->getHtmlElement()
            ->setHeight($this->height)
            ->addClass('rounded-circle')
            ->setExtra('loading="lazy"')
            ->render();

        $this->render .= '  </a>
                    <ul
                      class="dropdown-menu dropdown-menu-end"
                      aria-labelledby="navbarDropdownMenuAvatar"
                    >';

        if ($this->menu) {
            if ($this->menu->getSource()) {
                foreach ($this->menu->getSource() as $label => $entry) {
                    if (is_string($entry)) {
                        // Menu entry data was specified as just the URL in a string
                        $entry = ['url' => $entry];
                    }

                    $this->render .= '  <li>
                                    <a class="dropdown-item" href="' . $entry['url'] . '">' . $label . '</a>
                                </li>';
                }
            }
        }

        $this->render .= '  </ul>
                  </div>' . PHP_EOL;

        return $this->render;
    }
}