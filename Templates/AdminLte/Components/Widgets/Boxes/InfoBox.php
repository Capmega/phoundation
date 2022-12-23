<?php

namespace Templates\AdminLte\Components\Widgets\Boxes;



/**
 * AdminLte Plugin InfoBox class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class InfoBox extends \Phoundation\Web\Http\Html\Components\Widgets\Boxes\InfoBox
{
    /**
     * Renders and returns the HTML for this SmallBox object
     *
     * @inheritDoc
     */
    public function render(): ?string
    {
        $html = '   <div class="info-box shadow-none">
                      <span class="info-box-icon bg-' . $this->mode . '"><i class="far ' . $this->icon . '"></i></span>
        
                      <div class="info-box-content">
                        <span class="info-box-text">' . $this->title . '</span>
                        <span class="info-box-number">' . $this->value . '</span>
                      </div>
                      ' . $this->description . '
                    </div>';

        return $html;
    }
}