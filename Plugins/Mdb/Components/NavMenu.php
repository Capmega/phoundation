<?php

namespace Plugins\Mdb\Components;

use Phoundation\Core\Strings;
use Phoundation\Seo\Seo;
use Phoundation\Web\Http\Html\Elements\ElementsBlock;
use Phoundation\Web\Http\Url;



/**
 * MDB Plugin NavMenu class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\Mdb
 */
class NavMenu extends ElementsBlock
{
    /**
     * The menu data
     *
     * @var array|null
     */
    protected ?array $menu = null;



    /**
     * Footer class constructor
     */
    public function __construct()
    {
        parent::__construct();
    }



    /**
     * Returns a new footer object
     *
     * @return static
     */
    public static function new(): static
    {
        return new static();
    }



    /**
     * Returns the menu data
     *
     * @return array
     */
    public function getMenu(): array
    {
        return $this->menu;
    }



    /**
     * Sets the menu data
     *
     * @param array|null $menu
     * @return NavMenu
     */
    public function setMenu(?array $menu): static
    {
        $this->menu = $menu;
        return $this;
    }



    /**
     * Renders and returns the HTML for the footer
     *
     * @return string
     */
    public function render(): string
    {
        return $this->renderMenu($this->menu, 'nav navbar-nav me-auto mb-2 mb-lg-0');
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

        $html = ' <ul class="' . $ul_class . '">';

        foreach ($menu as $label => $url) {
            if (is_array($url)) {
                // This is a sub menu, recurse!
                $html .= '<li class="nav-item dropdown">
                              <a class="nav-link dropdown-toggle" data-mdb-toggle="dropdown" id="navbarDropdownMenu' . Strings::capitalize($label) . '">
                                ' . $label . ' 
                              </a>
                              ' . $this->renderSubMenu($url, 'dropdown-menu', ' aria-labelledby="navbarDropdownMenu' . Strings::capitalize($label) . '"') . '
                          </li>';
            } else {
                $html .= '  <li class="nav-item">
                              <a class="nav-link" href="' . Url::build($url)->www() . '">' . $label . '</a>
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

        $html = ' <ul class="' . $ul_class . '"' . $ul_attributes . '>';

        foreach ($menu as $label => $url) {
            if (is_array($url)) {
                // This is a sub menu, recurse!
                $html .= '<li>
                              <a class="dropdown-item" href="#">
                                ' . $label . ' &raquo;
                              </a>
                              ' . $this->renderSubMenu($url, 'dropdown-menu dropdown-submenu') . '
                          </li>';
            } else {
                $html .= '  <li>
                              <a class="dropdown-item" href="' . Url::build($url)->www() . '">' . $label . '</a>
                            </li>';
            }
        }

        $html .= '</ul>';

        return $html;
    }
}