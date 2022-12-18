<?php

namespace Templates\Mdb\Components;

use Phoundation\Core\Session;
use Phoundation\Web\Http\Html\Components\Panel;
use Phoundation\Web\Http\Html\Elements\Img;
use Templates\Mdb\Modals\SignInModal;



/**
 * MDB Plugin TopPanel class
 *
 * 
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\Mdb
 */
class TopPanel extends Panel
{
    /**
     * TopPanel class constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->getModals()->setRequired('sign-in');
        $this->getModals()->add('sign-in', new SignInModal());
    }



    /**
     * Renders and returns the NavBar
     *
     * @return string
     */
    public function render(): string
    {
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
                ->setSrc('/logos/phoundation/phoundation-top.png')
                ->setAlt('The Mdb logo')
                ->addAttributes([
                    'loading' => 'lazy',
                    'height'  => 50
                ])
                ->render(). '
                          </a>
                          ' . $this->menu->render() . '
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
                          ' . $this->profile_image->render() . '
                        </div>
                        <!-- Right elements -->
                      </div>
                      <!-- Container wrapper -->
                    </nav>
                    <!-- Navbar -->';

        $html .= $this->modals->render() . PHP_EOL;

        return $html;
    }
}
