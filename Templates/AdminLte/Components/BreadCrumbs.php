<?php

namespace Templates\AdminLte\Components;

use Phoundation\Web\Http\Url;



/**
 * AdminLte Plugin BreadCrumbs class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class BreadCrumbs extends \Phoundation\Web\Http\Html\Components\BreadCrumbs
{
    /**
     * Render the HTML for this AdminLte
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
                            <a href="' . Url::build()->www() . '" class="nav-link">' . tr('Home') . '</a>
                        </li>';

        if ($this->source) {
            foreach ($this->source as $url => $label) {
                $return . '<li class="nav-item d-none d-sm-inline-block">
                                <a href="' . Url::build($url)->www() . '" class="nav-link">' . $label . '</a>
                            </li>';
            }
        }

        return $return . '</ul>';;
    }
}