<?php

namespace Templates\AdminLte\Html\Components;

use Phoundation\Core\Strings;
use Phoundation\Web\Http\Html\Html;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Http\Html\Renderer;


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
class BreadCrumbs extends Renderer
{
    /**
     * BreadCrumbs class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Components\BreadCrumbs $element)
    {
        parent::__construct($element);
    }


    /**
     * Renders and returns the HTML for this component
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $this->render = ' <ol class="breadcrumb float-sm-right">';

        if ($this->element->getSource()) {
            $count = count($this->element->getSource());

            foreach ($this->element->getSource() as $url => $label) {
                $label = Strings::truncate($label, 48);

                if (!--$count) {
                    // The last item is the active item
                    $this->render .= '<li class="breadcrumb-item active">' . Html::safe($label) . '</li>';

                } else {
                    $this->render .= '<li class="breadcrumb-item"><a href="' . Html::safe(UrlBuilder::getWww($url)) . '">' . Html::safe($label) . '</a></li>';
                }
            }
        }

        $this->render .= '</ol>';

        return parent::render();
    }
}