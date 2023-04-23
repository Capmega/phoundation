<?php

namespace Templates\None\Html\Components\Widgets\Boxes;

use Phoundation\Web\Http\Html\Renderer;



/**
 * None Plugin InfoBox class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\None
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
                              <span class="info-box-icon bg-' . $this->element->getMode() . '"><i class="far ' . $this->element->getIcon() . '"></i></span>
                
                              <div class="info-box-content">
                                <span class="info-box-text">' . $this->element->getTitle() . '</span>
                                <span class="info-box-number">' . $this->element->getValue() . '</span>
                              </div>
                              ' . $this->element->getDescription() . '
                            </div>';

        return parent::render();
    }
}