<?php

declare(strict_types=1);


namespace Templates\Mdb\Html\Components;

use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Html;
use Phoundation\Web\Http\Html\Renderer;
use Phoundation\Web\Http\UrlBuilder;


/**
 * MDB Plugin Menu class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\Mdb
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
     * Renders and returns the HTML for the footer
     *
     * @return string|null
     */
    public function render(): ?string
    {
        return $this->renderMenu($this->render_object->getSource(), 'nav navbar-nav me-auto mb-2 mb-lg-0');
    }


    /**
     * Renders and returns the specified menu entry
     *
     * @param array|null $menu
     * @param string $ul_class
     * @return string
     */
    protected function renderMenu(?array $menu, string $ul_class): string
    {
        if (!$menu) {
            // No menu specified, return nothing
            return '';
        }

        $html = ' <ul class="' . Html::safe($ul_class) . '">';

        foreach ($menu as $label => $entry) {
            if (!is_array($entry)) {
                // Invalid entry, skip!
                throw new OutOfBoundsException('Invalid menu entry ":label" detected, skipping', [
                    ':label' => $label
                ]);
            }

            if (is_array(isset_get($entry['menu']))) {
                // This is a sub menu, recurse!
                $html .= '<li class="nav-item dropdown">
                              <a class="nav-link dropdown-toggle" data-mdb-toggle="dropdown" id="navbarDropdownMenu' . Html::safe(Strings::capitalize($label)) . '">
                                ' . Html::safe($label) . ' 
                              </a>
                              ' . $this->renderSubMenu($entry['menu'], 'dropdown-menu', ' aria-labelledby="navbarDropdownMenu' . Html::safe(Strings::capitalize($label)) . '"') . '
                          </li>';
            } else {
                $html .= '  <li class="nav-item">
                              <a class="nav-link" href="' . Html::safe(UrlBuilder::getWww(isset_get($entry['url']))) . '">' . Html::safe($label) . '</a>
                            </li>';
            }
        }

        $html .= '</ul>';

        return $html;
    }


    /**
     * Renders and returns the specified menu entry
     *
     * @param array|null $menu
     * @param string $ul_class
     * @param string $ul_attributes
     * @return string|null
     */
    protected function renderSubMenu(?array $menu, string $ul_class, string $ul_attributes = ''): ?string
    {
        if (!$menu) {
            // No menu specified, return nothing
            return '';
        }

        $html = ' <ul class="' . Html::safe($ul_class) . '"' . $ul_attributes . '>';

        foreach ($menu as $label => $entry) {
            if (!is_array($entry)) {
                // Invalid entry, skip!
                throw new OutOfBoundsException('Invalid menu entry ":label" detected, skipping', [
                    ':label' => $label
                ]);
            }

            if (is_array(isset_get($entry['menu']))) {
                // This is a sub menu, recurse!
                $html .= '<li>
                              <a class="dropdown-item" href="#">
                                ' . Html::safe($label) . ' &raquo;
                              </a>
                              ' . $this->renderSubMenu($entry['menu'], 'dropdown-menu dropdown-submenu') . '
                          </li>';
            } else {
                $html .= '  <li>
                              <a class="dropdown-item" href="' . Html::safe(UrlBuilder::getWww($entry['url'])) . '">' . $label . '</a>
                            </li>';
            }
        }

        $html .= '</ul>';

        return $html;
    }
}