<?php

namespace Templates\AdminLte\Components;

use Phoundation\Web\Http\Url;



/**
 * AdminLte Plugin TopMenu class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class TopMenu extends \Phoundation\Web\Http\Html\Components\Menu
{
    /**
     * Default the top panel menu
     *
     * @return array
     */
    public function getSource(): array
    {
        if (!$this->source) {
            $this->source = [
                tr('Front-end') => ['url' => '/'],
            ];
        }

        return $this->source;
    }



    /**
     * Render the HTML for the AdminLte top menu
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

        if ($this->getSource()) {
            foreach ($this->source as $label => $entry) {
                if (is_string($entry))  {
                    $entry = ['url' => $entry];
                }

                $return .= '<li class="nav-item d-none d-sm-inline-block">
                                <a href="' . Url::build($entry['url'])->www() . '" class="nav-link">' . $label . '</a>
                            </li>';
            }
        }

        return $return . '</ul>';;
    }
}