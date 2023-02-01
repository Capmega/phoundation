<?php

namespace Templates\AdminLte\Html\Components;

use Phoundation\Web\Http\Html\Components\MessagesDropDown;
use Phoundation\Web\Http\Html\Components\NotificationsDropDown;
use Phoundation\Web\Http\Html\Renderer;
use Phoundation\Web\Http\UrlBuilder;


/**
 * AdminLte Plugin TopPanel class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class TopPanel extends Renderer
{
    /**
     * TopPanel class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Components\TopPanel $element)
    {
        parent::__construct($element);
    }



    /**
     * Renders and returns the top panel
     *
     * @return string|null
     */
    public function render(): ?string
    {
        // TODO Change this hard coded menu below for a flexible one
//        $left_menu = $this->element->getMenu()?->render();

        $left_menu    = ' <ul class="navbar-nav">
                            <li class="nav-item">
                              <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                            </li>
                            <li class="nav-item d-none d-sm-inline-block">
                              <a href="' . UrlBuilder::getCurrent() . '" class="nav-link">' . tr('Home') . '</a>
                            </li>
                          </ul>';

        $this->render = ' <nav class="main-header navbar navbar-expand navbar-white navbar-light">
                            <!-- Left navbar links -->
                            ' . $left_menu . '                    
                            <!-- Right navbar links -->
                            <ul class="navbar-nav ml-auto">
                              <!-- Navbar Search -->
                              <li class="nav-item">
                                <a class="nav-link" data-widget="navbar-search" href="#" role="button">
                                  <i class="fas fa-search"></i>
                                </a>
                                <div class="navbar-search-block">
                                  <form class="form-inline">
                                    <div class="input-group input-group-sm">
                                      <input class="form-control form-control-navbar" type="search" placeholder="' . tr('Search everywhere') . '" aria-label="' . tr('Search everywhere') . '">
                                      <div class="input-group-append">
                                        <button class="btn btn-navbar" type="submit">
                                          <i class="fas fa-search"></i>
                                        </button>
                                        <button class="btn btn-navbar" type="button" data-widget="navbar-search">
                                          <i class="fas fa-times"></i>
                                        </button>
                                      </div>
                                    </div>
                                  </form>
                                </div>
                              </li>
                        
                               
                              <!-- Messages Dropdown Menu -->
                              <li class="nav-item dropdown">
                                ' . $this->element->getMessagesDropDown()?->render() . '
                              </li>
                              <!-- Notifications Dropdown Menu -->
                              <li class="nav-item dropdown">
                                ' . $this->element->getNotificationsDropDown()?->render() . '
                              </li>
                              <li class="nav-item">
                                <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                                  <i class="fas fa-expand-arrows-alt"></i>
                                </a>
                              </li>
                              <li class="nav-item">
                                <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button">
                                  <i class="fas fa-th-large"></i>
                                </a>
                              </li>
                              <li class="nav-item">
                                <a class="nav-link" href="' . UrlBuilder::getWww('sign-out.html') . '" role="button">
                                  <i class="fas fa-sign-out-alt"></i>
                                </a>
                              </li>
                            </ul>
                          </nav>';

        return parent::render();
    }
}