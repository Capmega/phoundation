<?php

namespace Templates\None\Html\Components;

use Phoundation\Web\Http\Html\Renderer;



/**
 * None Plugin Menu class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\None
 */
class Menu extends Renderer
{
    /**
     * Menu class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Components\Menu $element)
    {
        parent::__construct($element);
    }



    /**
     * Renders the HTML for the menu
     *
     * @todo Add caching of the menu structure
     * @return string|null
     */
    public function render(): ?string
    {
        return $this->renderMenu($this->element->getSource(), 0);
    }



    /**
     * Renders the HTML for the sidebar menu
     *
     * @param array $source
     * @param int $sub_menu
     * @return string
     */
    protected function renderMenu(array $source, int $sub_menu): string
    {
        if ($sub_menu) {
            $html = '<ul class="nav nav-treeview sub-menu-' . $sub_menu . '">';
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
                    $html .= $this->renderMenu($entry['menu'], ++$sub_menu);
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