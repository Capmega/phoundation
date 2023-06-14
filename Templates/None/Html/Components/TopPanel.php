<?php

declare(strict_types=1);


namespace Templates\None\Html\Components;

use Phoundation\Core\Session;
use Phoundation\Web\Http\Html\Enums\DisplayMode;
use Phoundation\Web\Http\Html\Html;
use Phoundation\Web\Http\Html\Renderer;
use Phoundation\Web\Http\UrlBuilder;


/**
 * None Plugin TopPanel class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\None
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

        // If impersonated, change top panel color and add impersonation message
        if (Session::isImpersonated()) {
            $this->element->setMode(DisplayMode::danger);
            $message = tr('(Impersonated by ":user")', [':user' => Session::getRealUser()->getDisplayName()]);
        } else {
            $this->element->setMode(DisplayMode::white);
        }

        // Top level message?
        if (isset($message)) {
            $message = '    <li class="nav-item d-none d-sm-inline-block">
                              <a href="#" class="nav-link">' . $message . '</a>
                            </li>';
        }

        // Build the left menu
        $left_menu    = ' <ul class="navbar-nav">
                            <li class="nav-item">
                              <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                            </li>
                            <li class="nav-item d-none d-sm-inline-block">
                              <a href="' . Html::safe(UrlBuilder::getCurrent()) . '" class="nav-link">' . tr('Home') . '</a>
                            </li>
                            ' . isset_get($message) . '
                          </ul>';

        // Build the panel
        $this->render = ' <nav class="main-header navbar navbar-expand navbar-' . Html::safe($this->element->getMode()->value) . ' navbar-light">
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
                              <li class="nav-item dropdown">                                  
                                  ' . $this->element->getLanguagesDropDown()?->render() . '
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
                                <a class="nav-link" href="' . Html::safe(UrlBuilder::getWww('sign-out.html')) . '" role="button">
                                  <i class="fas fa-sign-out-alt"></i>
                                </a>
                              </li>
                            </ul>
                          </nav>';

        return parent::render();
    }
}