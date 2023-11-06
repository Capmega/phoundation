<?php

declare(strict_types=1);


namespace Templates\AdminLte\Html\Components;

use Phoundation\Core\Strings;
use Phoundation\Web\Html\Html;
use Phoundation\Web\Html\Renderer;
use Phoundation\Web\Http\UrlBuilder;


/**
 * AdminLte Plugin BreadCrumbs class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class BreadCrumbs extends Renderer
{
    /**
     * BreadCrumbs class constructor
     */
    public function __construct(\Phoundation\Web\Html\Components\BreadCrumbs $element)
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

        if ($this->render_object->getSource()) {
            $count = count($this->render_object->getSource());

            foreach ($this->render_object->getSource() as $url => $label) {
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