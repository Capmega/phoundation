<?php

namespace Templates\None\Html\Components;

use Phoundation\Web\Http\Html\Renderer;
use Phoundation\Web\Http\UrlBuilder;

/**
 * None Plugin TopMenu class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\None
 */
class TopMenu extends Renderer
{
    /**
     * TopMenu class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Components\TopMenu $element)
    {
        parent::__construct($element);
    }


    /**
     * Render the HTML for the None top menu
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $return = '<ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                        </li>
                        <li class="nav-item d-none d-sm-inline-block">
                            <a href="' . UrlBuilder::getCurrent() . '" class="nav-link">' . tr('Home') . '</a>
                        </li>';

        if ($this->element->getSource()) {
            foreach ($this->element->getSource() as $label => $entry) {
                if (is_string($entry))  {
                    $entry = ['url' => $entry];
                }

                $return .= '<li class="nav-item d-none d-sm-inline-block">
                                <a href="' . $entry['url'] . '" class="nav-link">' . $label . '</a>
                            </li>';
            }
        }

        return $return . '</ul>';;
    }
}