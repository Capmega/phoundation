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
    public function render(): ?string
    {
        $html = ' <ol class="breadcrumb float-sm-right">';

        if ($this->source) {
            $count = count($this->source);

            foreach ($this->source as $url => $label) {
                if (!--$count) {
                    // The last item is the active item
                    $html .= '<li class="breadcrumb-item active">' . $label . '</li>';

                } else {
                    $html .= '<li class="breadcrumb-item"><a href="' . Url::build($url)->www() . '">' . $label . '</a></li>';
                }
            }
        }

        return $html . '</ol>';
    }
}