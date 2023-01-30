<?php

namespace Templates\AdminLte\Components;

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
class TopPanel extends \Phoundation\Web\Http\Html\Components\TopPanel
{
    /**
     * The top notifications drop down
     *
     * @var NotificationsDropDown $notifications
     */
    protected NotificationsDropDown $notifications;

    /**
     * The top messages drop down
     *
     * @var MessagesDropDown $messages
     */
    protected MessagesDropDown $messages;



    /**
     * TopPanel class constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->messages      = MessagesDropDown::new();
        $this->notifications = NotificationsDropDown::new();
    }



    /**
     * Returns the notifications drop down object
     *
     * @return NotificationsDropDown
     */
    public function getNotificationsDropDown(): NotificationsDropDown
    {
        return $this->notifications;
    }



    /**
     * Returns the notifications drop down object
     *
     * @return MessagesDropDown
     */
    public function getMessagesDropDown(): MessagesDropDown
    {
        return $this->messages;
    }



    /**
     * Renders and returns the top panel
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $this->render = ' <nav class="main-header navbar navbar-expand navbar-white navbar-light">
                            <!-- Left navbar links -->
                            ' . $this->getMenu()?->render() . '                    
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
                                ' . $this->messages?->render() . '
                              </li>
                              <!-- Notifications Dropdown Menu -->
                              <li class="nav-item dropdown">
                                ' . $this->notifications?->render() . '
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