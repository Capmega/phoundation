<?php

namespace Plugins\Mdb;

use Phoundation\Content\Images\UsesImage;
use Phoundation\Content\Images\Image;
use Phoundation\Web\Http\Html\ElementsBlock;



/**
 * Phoundation template class
 *
 * This class is an example template for your website
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\Mdb
 */
class ImageMenu extends ElementsBlock
{
    use UsesImage;



    /**
     * The menu items [label => url]
     *
     * @var array|null $menu
     */
    protected ?array $menu = null;

    /**
     * The image height in pixels
     *
     * @var int $height
     */
    protected int $height = 25;



    /**
     * ImageMenu class constructor
     *
     * @param Image|string|null $image
     * @param array|null $menu
     */
    public function __construct(Image|string|null $image, ?array $menu)
    {
        $this->menu  = $menu;

        if (!is_object($image)) {
            $image = new Image($image);
        }

        $this->image = $image;
    }



    /**
     * Returns a new ElementsBlock
     *
     * @param Image|string|null $image
     * @param array|null $menu
     * @return static
     */
    public static function new(Image|string|null $image, ?array $menu): static
    {
        return new static($image, $menu);
    }



    /**
     * Renders and returns the profile image block HTML
     *
     * @return string
     */
    public function render(): string
    {
        $html = ' <!-- Avatar -->
                  <div class="dropdown">
                    <a
                      class="dropdown-toggle d-flex align-items-center hidden-arrow"
                      href="#"
                      id="navbarDropdownMenuAvatar"
                      role="button"
                      data-mdb-toggle="dropdown"
                      aria-expanded="false"
                    >';

        $html .= $this->image->getElement()
            ->setHeight($this->height)
            ->addClass('rounded-circle')
            ->setExtra('loading="lazy"')
            ->render();

        $html .= '  </a>
                    <ul
                      class="dropdown-menu dropdown-menu-end"
                      aria-labelledby="navbarDropdownMenuAvatar"
                    >';

        foreach ($this->menu as $label => $url) {
            $html .= '<li>
                        <a class="dropdown-item" href="' . $url . '">' . $label . '</a>
                      </li>';
        }

        $html .= '  </ul>
                  </div>';

        return $html;
    }
}