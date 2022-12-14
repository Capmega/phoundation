<?php

namespace Plugins\AdminLte\Components;



/**
 * AdminLte Plugin Menu class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\AdminLte
 */
class Menu extends \Phoundation\Web\Http\Html\Components\Menu
{
    /**
     * Renders the HTML for the menu
     *
     * @return string
     */
    public function render(): string
    {
        return $this->renderMenu($this->menu);
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

        $html .= '</ul>' . PHP_EOL;

        return $html;
    }
}