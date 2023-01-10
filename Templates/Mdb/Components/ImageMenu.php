<?php

namespace Templates\Mdb\Components;

use Phoundation\Exception\OutOfBoundsException;



/**
 * MDB Plugin ImageMenu class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\Mdb
 */
class ImageMenu extends \Phoundation\Web\Http\Html\Components\ImageMenu
{
    /**
     * Renders and returns the image menu block HTML
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if (!isset($this->image)) {
            throw new OutOfBoundsException(tr('Cannot render ImageMenu object HTML, no image specified'));
        }
//.        <button type="button" class="btn btn-primary" data-mdb-toggle="modal" data-mdb-target="#exampleModal" style=""> Launch demo modal </button>

        $this->render = ' <div class="dropdown image-menu">
                    <a
                      class="' . ($this->menu ? 'dropdown-toggle ' : '') . 'd-flex align-items-center hidden-arrow"
                      href="' . ($this->menu ? '#' : $this->url) . '"
                      id="navbarDropdownMenuAvatar"
                      ' . ($this->menu ? 'role="button" data-mdb-toggle="dropdown"' : ($this->modal_selector ? 'data-mdb-toggle="modal" data-mdb-target="' . $this->modal_selector . '"' : null)) . '                    
                      aria-expanded="false"
                    >';

        $this->render .= $this->image->getHtmlElement()
            ->setHeight($this->height)
            ->addClass('rounded-circle')
            ->setExtra('loading="lazy"')
            ->render();

        $this->render .= '  </a>
                    <ul
                      class="dropdown-menu dropdown-menu-end"
                      aria-labelledby="navbarDropdownMenuAvatar"
                    >';

        if ($this->menu) {
            foreach ($this->menu->getSource() as $label => $entry) {
                if (is_string($entry)) {
                    // Menu entry data was specified as just the URL in a string
                    $entry = ['url' => $entry];
                }

                $this->render .= '<li>
                            <a class="dropdown-item" href="' . $entry['url'] . '">' . $label . '</a>
                          </li>';
            }
        }

        $this->render .= '  </ul>
                  </div>' . PHP_EOL;

        return $this->render;
    }
}