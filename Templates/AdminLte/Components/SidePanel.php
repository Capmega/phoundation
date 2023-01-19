<?php

namespace Templates\AdminLte\Components;

use Phoundation\Core\Config;
use Phoundation\Core\Session;
use Phoundation\Core\Strings;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Http\UrlBuilder;


/**
 * AdminLte Plugin SidePanel class
 *
 * 
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class SidePanel extends \Phoundation\Web\Http\Html\Components\SidePanel
{
    /**
     * Renders and returns the sidebar
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $this->render = ' <aside class="main-sidebar sidebar-dark-primary elevation-4">
                            <a href="' . UrlBuilder::current() . '" class="brand-link">
                              <img src="' . UrlBuilder::img('img/logos/phoundation-64x64.png') . '" alt="' . tr(':project logo', [':project' => Strings::capitalize(Config::get('project.name'))]) . '" class="brand-image img-circle elevation-3" style="opacity: .8">
                              <span class="brand-text font-weight-light">' . Strings::capitalize(Config::get('project.name')) . '</span>
                            </a>
                            <div class="sidebar">
                              <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                                <div class="image">
                                  ' . Session::getUser()->getPicture()
                                        ->getHtmlElement()
                                            ->setClass('img-circle elevation-2')
                                            ->setAlt(tr('Profile picture for :user', [':user' => Session::getUser()->getDisplayName()]))
                                            ->render() . '
                                </div>
                                <div class="info">
                                  <a href="' . (Session::getUser()->isGuest() ? '#' : UrlBuilder::www('/users/entry/' . urlencode(Session::getUser()->getEmail()))) . '" class="d-block">' . Session::getUser()->getDisplayName() . '</a>
                                </div>
                              </div>
                              <div class="form-inline">
                                <div class="input-group" data-widget="sidebar-search">
                                  <input class="form-control form-control-sidebar" type="search" placeholder="' . tr('Search menu') . '" aria-label="' . tr('Search menu') . '">
                                  <div class="input-group-append">
                                    <button class="btn btn-sidebar">
                                      <i class="fas fa-search fa-fw"></i>
                                    </button>
                                  </div>
                                </div><div class="sidebar-search-results"><div class="list-group"><a href="#" class="list-group-item"><div class="search-title"><strong class="text-light"></strong>N<strong class="text-light"></strong>o<strong class="text-light"></strong> <strong class="text-light"></strong>e<strong class="text-light"></strong>l<strong class="text-light"></strong>e<strong class="text-light"></strong>m<strong class="text-light"></strong>e<strong class="text-light"></strong>n<strong class="text-light"></strong>t<strong class="text-light"></strong> <strong class="text-light"></strong>f<strong class="text-light"></strong>o<strong class="text-light"></strong>u<strong class="text-light"></strong>n<strong class="text-light"></strong>d<strong class="text-light"></strong>!<strong class="text-light"></strong></div><div class="search-path"></div></a></div></div>
                              </div>
                        
                              <!-- Sidebar Menu -->
                              <nav>
                                ' . $this->menu?->render() . '                                
                              </nav>
                              <!-- /.sidebar-menu -->
                            </div>
                            <!-- /.sidebar -->
                          </aside>';

        $this->render .= $this->modals->render() . PHP_EOL;

        return parent::render();
    }
}