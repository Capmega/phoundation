<?php

namespace Plugins\AdminLte\Components;

use Phoundation\Core\Config;
use Phoundation\Core\Session;
use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Components\Panel;
use Phoundation\Web\Http\Url;



/**
 * AdminLte Plugin SidePanel class
 *
 * This class is an example template for your website
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\AdminLte
 */
class SidePanel extends Panel
{
    /**
     * Renders and returns the sidebar
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if (!$this->sign_in_modal) {
            throw new OutOfBoundsException(tr('Failed to render SidePanel component, no sign-in modal specified'));
        }

        $html = '<aside class="main-sidebar sidebar-dark-primary elevation-4">
                    <a href="' . Url::build()->www() . '" class="brand-link">
                        <img src="dist/img/AdminLTELogo.png" alt="' . tr(':project logo', [':project' => Strings::capitalize(Config::get('project.name'))]) . '" class="brand-image img-circle elevation-3" style="opacity: .8">
                        <span class="brand-text font-weight-light">' . Strings::capitalize(Config::get('project.name')) . '</span>
                    </a>
                    
                    <div class="sidebar os-host os-theme-light os-host-overflow os-host-overflow-y os-host-resize-disabled os-host-scrollbar-horizontal-hidden os-host-transition">
                        <div class="os-resize-observer-host observed">
                            <div class="os-resize-observer" style="left: 0px; right: auto;">                       
                            </div>
                        </div>
                        <div class="os-size-auto-observer observed" style="height: calc(100% + 1px); float: left;">
                            <div class="os-resize-observer">
                            </div>                   
                        </div>
                        <div class="os-content-glue" style="margin: 0px -8px; width: 249px; height: 492px;">
                        </div>
                        <div class="os-padding">
                            <div class="os-viewport os-viewport-native-scrollbars-invisible" style="overflow-y: scroll;">
                                <div class="os-content" style="padding: 0px 8px; height: 100%; width: 100%;">                  
                                    <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                                        <div class="image">
                                            <img src="' . Session::getUser()->getPicture()->getFile() . '" class="img-circle elevation-2" alt="' . tr('Image for :user', [':user' => Session::getUser()->getDisplayName()]) . '">
                                        </div>
                                        <div class="info">
                                            <a href="' . (Session::getUser()->isGuest() ? '' : Url::build('/users/entry/' . urlencode(Session::getUser()->getEmail()))->www()) . '" class="d-block">' . Session::getUser()->getDisplayName() . '</a>
                                        </div>
                                    </div>               
                                    <div class="form-inline">
                                        <div class="input-group" data-widget="sidebar-search">
                                            <input class="form-control form-control-sidebar" type="search" placeholder="' . tr('Search') . '" aria-label="' . tr('Search') . '">
                                            <div class="input-group-append">
                                                <button class="btn btn-sidebar"><i class="fas fa-search fa-fw"></i></button>
                                            </div>
                                        </div>
                                        <div class="sidebar-search-results">
                                            <div class="list-group">
                                                <a href="#" class="list-group-item">
                                                    <div class="search-title">
                                                        <strong class="text-light"></strong>N<strong class="text-light"></strong>o<strong class="text-light"></strong> <strong class="text-light"></strong>e<strong class="text-light"></strong>l<strong class="text-light"></strong>e<strong class="text-light"></strong>m<strong class="text-light"></strong>e<strong class="text-light"></strong>n<strong class="text-light"></strong>t<strong class="text-light"></strong> <strong class="text-light"></strong>f<strong class="text-light"></strong>o<strong class="text-light"></strong>u<strong class="text-light"></strong>n<strong class="text-light"></strong>d<strong class="text-light"></strong>!<strong class="text-light"></strong>
                                                    </div>
                                                    <div class="search-path">
                                                    </div>
                                                </a>
                                            </div>
                                        </div>
                                    </div>               
                                    <nav class="mt-2">
                                        ' . ($this->menu ? $this->renderMenu($this->menu) : '') . '                                
                                    </nav>                   
                                </div>
                            </div>
                        </div>
                        <div class="os-scrollbar os-scrollbar-horizontal os-scrollbar-unusable os-scrollbar-auto-hidden">
                            <div class="os-scrollbar-track">
                                <div class="os-scrollbar-handle" style="width: 100%; transform: translate(0px, 0px);">                       
                                </div>
                            </div>
                        </div>
                        <div class="os-scrollbar os-scrollbar-vertical os-scrollbar-auto-hidden">
                            <div class="os-scrollbar-track">
                                <div class="os-scrollbar-handle" style="height: 44.8182%; transform: translate(0px, 0px);">                    
                                </div>
                            </div>
                        </div>
                            <div class="os-scrollbar-corner">           
                            </div>
                        </div>                    
                    </div>                    
                </aside>';

        $html .= $this->sign_in_modal->render() . PHP_EOL;

        return $html;
    }



    /**
     * Renders the HTML for the sidebar menu
     *
     * @param array $menu
     * @param bool $sub_menu
     * @return string
     */
    protected function renderMenu(array $menu, bool $sub_menu = false): string
    {
        if ($sub_menu) {
            $html = '<ul class="nav nav-treeview">';
        } else {
            $html = '<ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">';
        }

        foreach ($menu as $label => $entry) {
            // Build menu entry
            $html .= '<li class="nav-item">
                        <a href="' . (isset_get($entry['url']) ?? '#') . '" class="nav-link">
                            <i class="nav-icon fas ' . isset_get($entry['icon']) . '"></i>
                            <p>' . $label . (isset($entry['menu']) ? '<i class="right fas fa-angle-left"></i>' : (isset($entry['badge']) ? '<span class="right badge badge-' . $entry['badge']['type'] . '">' . $entry['badge']['label'] . '</span>' : '')) . '</p>
                        </a>';

            if (isset($entry['menu'])) {
                $html .= $this->renderMenu($entry['menu'], true);
            }

            $html .= '</li>';
        }

        $html .= '</ul>' . $this->sign_in_modal->render() . PHP_EOL;

        return $html;
    }
}