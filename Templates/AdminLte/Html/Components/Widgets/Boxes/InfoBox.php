<?php

namespace Templates\AdminLte\Html\Components\Widgets\Boxes;

use Phoundation\Web\Http\Html\Html;
use Phoundation\Web\Http\Html\Renderer;


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
class InfoBox extends Renderer
{
    /**
     * InfoBox class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Components\Widgets\Boxes\InfoBox $element)
    {
        parent::__construct($element);
    }


    /**
     * Renders and returns the HTML for this SmallBox object
     *
     * @inheritDoc
     */
    public function render(): ?string
    {
        $this->render = '   <div class="info-box shadow-none">
                              <span class="info-box-icon bg-' . Html::safe($this->element->getMode()->value) . '"><i class="far ' . Html::safe($this->element->getIcon()) . '"></i></span>
                
                              <div class="info-box-content">
                                <span class="info-box-text">' . Html::safe($this->element->getTitle()) . '</span>
                                <span class="info-box-number">' . Html::safe($this->element->getValue()) . '</span>
                              </div>
                              ' . Html::safe($this->element->getDescription()) . '
                            </div>';

        return parent::render();
    }
}