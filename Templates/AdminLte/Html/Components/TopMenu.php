<?php

declare(strict_types=1);


namespace Templates\AdminLte\Html\Components;

use Phoundation\Web\Http\Html\Html;
use Phoundation\Web\Http\Html\Renderer;
use Phoundation\Web\Http\UrlBuilder;


/**
 * AdminLte Plugin TopMenu class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
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
                            <a href="' . Html::safe(UrlBuilder::getCurrentDomainRootUrl()) . '" class="nav-link">' . tr('Home') . '</a>
                        </li>
                        <li class="nav-item d-none d-sm-inline-block">
                            <a href="' . Html::safe(UrlBuilder::getWww('/demo')) . '" class="nav-link">' . tr('Demo') . '</a>
                        </li>';

        if ($this->render_object->getSource()) {
            foreach ($this->render_object->getSource() as $label => $entry) {
                if (is_string($entry))  {
                    $entry = ['url' => $entry];
                }

                $return .= '<li class="nav-item d-none d-sm-inline-block">
                                <a href="' . Html::safe($entry['url']) . '" class="nav-link">' . Html::safe($label) . '</a>
                            </li>';
            }
        }

        return $return . '</ul>';;
    }
}