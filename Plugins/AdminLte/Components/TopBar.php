<?php

namespace Plugins\AdminLte\Components;

use Phoundation\Exception\OutOfBoundsException;



/**
 * AdminLte Plugin TopBar class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\AdminLte
 */
class TopBar extends \Phoundation\Web\Http\Html\Components\TopBar
{
    /**
     * Renders and returns the NavBar
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if (!$this->sign_in_modal) {
            throw new OutOfBoundsException(tr('Failed to render TopBar component, no sign-in modal specified'));
        }

        $html = '<nav class="main-header navbar navbar-expand navbar-white navbar-light">
                    <!-- Left navbar links -->
                    ' . $this->bread_crumbs->render() . '
                    
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
                              <input class="form-control form-control-navbar" type="search" placeholder="' . tr('Search') . '" aria-label="' . tr('Search') . '">
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
                        ' . MessagesDropDown::new()->render() . '
                      </li>
                      <!-- Notifications Dropdown Menu -->
                      <li class="nav-item dropdown">
                        ' . NotificationsDropDown::new()->render() . '
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
                    </ul>
                  </nav>';

        $html .= $this->sign_in_modal->render() . PHP_EOL;

        return $html;
    }
}