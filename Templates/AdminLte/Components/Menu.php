<?php

namespace Templates\AdminLte\Components;



/**
 * AdminLte Plugin Menu class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
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
        return $this->renderMenu($this->source);
    }



    /**
     * Renders the HTML for the sidebar menu
     *
     * @param array $source
     * @param bool $sub_menu
     * @return string
     */
    protected function renderMenu(array $source, bool $sub_menu = false): string
    {
        if ($sub_menu) {
            $html = '<ul class="nav nav-treeview">';
        } else {
            $html = '<ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">';
        }

        foreach ($source as $label => $entry) {
            // Build menu entry
            if (isset($entry['url']) or isset($entry['menu'])) {
                $html .= '<li class="nav-item">
                            <a href="' . (isset_get($entry['url']) ?? '#') . '" class="nav-link">
                                ' . (isset($entry['icon']) ? '<i class="nav-icon fas ' . $entry['icon'] . '"></i>' : '') . '
                                <p>' . $label . (isset($entry['menu']) ? '<i class="right fas fa-angle-left"></i>' : (isset($entry['badge']) ? '<span class="right badge badge-' . $entry['badge']['type'] . '">' . $entry['badge']['label'] . '</span>' : '')) . '</p>
                            </a>';

                if (isset($entry['menu'])) {
                    $html .= $this->renderMenu($entry['menu'], true);
                }
            } else {
                // Not a clickable menu element, just a label
                $html .= '<li class="nav-header">
                                ' . (isset($entry['icon']) ? '<i class="nav-icon fas ' . $entry['icon'] . '"></i>' : '') . '
                                ' . strtoupper($label) . (isset($entry['badge']) ? '<span class="right badge badge-' . $entry['badge']['type'] . '">' . $entry['badge']['label'] . '</span>' : '');
            }

            $html .= '</li>';
        }

        $html .= '</ul>' . PHP_EOL;

        return $html;
    }
}