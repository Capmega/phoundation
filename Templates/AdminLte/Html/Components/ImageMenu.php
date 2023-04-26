<?php

namespace Templates\AdminLte\Html\Components;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Html;
use Phoundation\Web\Http\Html\Renderer;


/**
 * AdminLte Plugin ImageMenu class
 *
 * 
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class ImageMenu extends Renderer
{
    /**
     * ImageMenu class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Components\ImageMenu $element)
    {
        parent::__construct($element);
    }


    /**
     * Renders and returns the image menu block HTML
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if (!$this->element->getImage()) {
            throw new OutOfBoundsException(tr('Cannot render ImageMenu object HTML, no image specified'));
        }
//.        <button type="button" class="btn btn-primary" data-mdb-toggle="modal" data-mdb-target="#exampleModal" style=""> Launch demo modal </button>

        $this->render = ' <div class="dropdown image-menu">
                            <a class="' . ($this->element->getMenu() ? 'dropdown-toggle ' : '') . 'd-flex align-items-center hidden-arrow"
                              href="' . ($this->element->getMenu() ? '#' : Html::safe($this->element->getUrl())) . '"
                              id="navbarDropdownMenuAvatar" aria-expanded="false"
                              ' . ($this->element->getMenu() ? 'role="button" data-mdb-toggle="dropdown"' : ($this->element->getModalSelector() ? 'data-mdb-toggle="modal" data-mdb-target="' . Html::safe($this->element->getModalSelector()) . '"' : null)) . '>';

        $this->render .= $this->element->getImage()->getHtmlElement()
            ->setHeight($this->element->getHeight())
            ->addClass('rounded-circle')
            ->setExtra('loading="lazy"')
            ->render();

        $this->render .= '  </a>
                            <ul
                              class="dropdown-menu dropdown-menu-end"
                              aria-labelledby="navbarDropdownMenuAvatar"
                            >';

        if ($this->element->getMenu()) {
            foreach ($this->element->getMenu() as $label => $url) {
                $this->render .= '<li>
                                    <a class="dropdown-item" href="' . Html::safe($url) . '">' . Html::safe($label) . '</a>
                                  </li>';
            }

        }

        $this->render .= '      </ul>
                            </div>' . PHP_EOL;

        return parent::render();
    }
}