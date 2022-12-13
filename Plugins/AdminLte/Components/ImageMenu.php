<?php

namespace Plugins\AdminLte\Components;

use Phoundation\Exception\OutOfBoundsException;



/**
 * AdminLte Plugin ImageMenu class
 *
 * This class is an example template for your website
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\AdminLte
 */
class ImageMenu extends \Phoundation\Web\Http\Html\Components\ImageMenu
{
   /**
     * Renders and returns the image menu block HTML
     *
     * @return string
     */
    public function render(): string
    {
        if (!isset($this->image)) {
            throw new OutOfBoundsException(tr('Cannot render ImageMenu object HTML, no image specified'));
        }
//.        <button type="button" class="btn btn-primary" data-mdb-toggle="modal" data-mdb-target="#exampleModal" style=""> Launch demo modal </button>

        $html = ' <div class="dropdown image-menu">
                    <a
                      class="' . ($this->menu ? 'dropdown-toggle ' : '') . 'd-flex align-items-center hidden-arrow"
                      href="' . ($this->menu ? '#' : $this->url) . '"
                      id="navbarDropdownMenuAvatar"
                      ' . ($this->menu ? 'role="button" data-mdb-toggle="dropdown"' : ($this->modal_selector ? 'data-mdb-toggle="modal" data-mdb-target="' . $this->modal_selector . '"' : null)) . '                    
                      aria-expanded="false"
                    >';

        $html .= $this->image->getHtmlElement()
            ->setHeight($this->height)
            ->addClass('rounded-circle')
            ->setExtra('loading="lazy"')
            ->render();

        $html .= '  </a>
                    <ul
                      class="dropdown-menu dropdown-menu-end"
                      aria-labelledby="navbarDropdownMenuAvatar"
                    >';

        if ($this->menu) {
            foreach ($this->menu as $label => $url) {
                $html .= '<li>
                            <a class="dropdown-item" href="' . $url . '">' . $label . '</a>
                          </li>';
            }

        }

        $html .= '  </ul>
                  </div>' . PHP_EOL;

        return $html;
    }
}