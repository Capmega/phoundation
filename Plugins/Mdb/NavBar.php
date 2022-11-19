<?php

namespace Plugins\Mdb;

use Phoundation\Core\Session;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\ElementsBlock;
use Phoundation\Web\Http\Html\Img;



/**
 * MDB Plugin NavBar class
 *
 * This class is an example template for your website
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\Mdb
 */
class NavBar extends ElementsBlock
{
    /**
     * A list of items that will be displayed in the top menu bar in the specified order
     *
     * @var array $items
     */
    protected array $items = [];

    /**
     * The site logo
     *
     * @var string|null $logo
     */
    protected ?string $logo = null;

    /**
     * The site menu
     *
     * @var array|null
     */
    protected ?array $menu = null;

    /**
     * The profile image block
     *
     * @var ProfileImage|null
     */
    protected ?ProfileImage $profile = null;

    /**
     * The profile menu
     *
     * @var array|null
     */
    protected ?array $profile_menu = null;

    /**
     * The modal for the signin page
     *
     * @var Modal|null $signin_modal
     */
    protected ?Modal $signin_modal = null;



    /**
     * Sets the navbar profile menu
     *
     * @param array|null $menu
     * @return static
     */
    public function setProfileMenu(?array $menu): static
    {
        $this->profile_menu = $menu;
        return $this;
    }



    /**
     * Returns the navbar profile menu
     *
     * @return array|null
     */
    public function getProfileMenu(): ?array
    {
        return $this->profile_menu;
    }



    /**
     * Returns the navbar signin modal
     *
     * @return array|null
     */
    public function getSigninModal(): ?Modal
    {
        return $this->signin_modal;
    }



    /**
     * Sets the navbar signin modal
     *
     * @param Modal|null $signin_modal
     * @return static
     */
    public function setProfileMenugetSigninModal(?Modal $signin_modal): static
    {
        $this->signin_modal = $signin_modal;
        return $this;
    }



    /**
     * Renders and returns the NavBar
     *
     * @return string
     */
    public function render(): string
    {
        if (!$this->signin_modal) {
            throw new OutOfBoundsException(tr('Failed to render NavBar component, no signin modal specified'));
        }

        $html = '    <!-- Navbar -->
                    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
                      <!-- Container wrapper -->
                      <div class="container-fluid">
                        <!-- Toggle button -->
                        <button
                          class="navbar-toggler"
                          type="button"
                          data-mdb-toggle="collapse"
                          data-mdb-target="#navbarSupportedContent"
                          aria-controls="navbarSupportedContent"
                          aria-expanded="false"
                          aria-label="Toggle navigation"
                        >
                          <i class="fas fa-bars"></i>
                        </button>
                    
                        <!-- Collapsible wrapper -->
                        <div class="collapse navbar-collapse" id="navbarSupportedContent">
                          <!-- Navbar brand -->
                          <a class="navbar-brand mt-2 mt-lg-0" href="#">
                          ' . Img::new()
                                ->setSrc('/logos/phoundation/phoundation-top.jpg')
                                ->setAlt('The Phoundation logo')
                                ->addAttributes([
                                    'loading' => 'lazy',
                                    'height'  => 15
                                ])
                                ->render(). '
                          </a>
                          <!-- Left links -->
                          <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                            <li class="nav-item">
                              <a class="nav-link" href="#">Dashboard</a>
                            </li>
                            <li class="nav-item">
                              <a class="nav-link" href="#">Team</a>
                            </li>
                            <li class="nav-item">
                              <a class="nav-link" href="#">Projects</a>
                            </li>
                          </ul>
                          <!-- Left links -->
                        </div>
                        <!-- Collapsible wrapper -->
                    
                        <!-- Right elements -->
                        <div class="d-flex align-items-center">
                          <!-- Icon -->
                          <a class="text-reset me-3" href="#">
                            <i class="fas fa-shopping-cart"></i>
                          </a>
                    
                          <!-- Notifications -->
                          <div class="dropdown">
                            <a
                              class="text-reset me-3 dropdown-toggle hidden-arrow"
                              href="#"
                              id="navbarDropdownMenuLink"
                              role="button"
                              data-mdb-toggle="dropdown"
                              aria-expanded="false"
                            >
                              <i class="fas fa-bell"></i>
                              <span class="badge rounded-pill badge-notification bg-danger">1</span>
                            </a>
                            <ul
                              class="dropdown-menu dropdown-menu-end"
                              aria-labelledby="navbarDropdownMenuLink"
                            >
                              <li>
                                <a class="dropdown-item" href="#">Some news</a>
                              </li>
                              <li>
                                <a class="dropdown-item" href="#">Another news</a>
                              </li>
                              <li>
                                <a class="dropdown-item" href="#">Something else here</a>
                              </li>
                            </ul>
                          </div>
                          ' . ProfileImage::new()
                                ->setImage(Session::getUser()->getPicture())
                                ->setMenu($this->profile_menu)
                                ->render()
                            . '
                        </div>
                        <!-- Right elements -->
                      </div>
                      <!-- Container wrapper -->
                    </nav>
                    <!-- Navbar -->';

        $html .= $this->signin_modal::new()->render() . PHP_EOL;

        return $html;
    }
}