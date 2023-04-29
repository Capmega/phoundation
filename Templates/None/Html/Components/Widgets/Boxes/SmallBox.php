<?php

namespace Templates\None\Html\Components\Widgets\Boxes;

use Phoundation\Web\Http\Html\Renderer;


/**
 * None Plugin SmallBox class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\None
 */
class SmallBox extends Renderer
{
    /**
     * SmallBox class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Components\Widgets\Boxes\SmallBox $element)
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
        $this->render = '   <div class="small-box bg-' . $this->element->getMode()->value . ($this->shadow ? ' ' . $this->shadow : '') . '">
                              <div class="inner">
                                <h3>' . $this->element->getValue() . '</h3>       
                                <p>' . $this->element->getTitle() . '</p>
                              </div>
                              ' . (($this->element->getProgress() !== null) ? '   <div class="progress">
                                                                      <div class="progress-bar" style="width: ' . $this->element->getProgress() . '%"></div>
                                                                    </div>' : '') . '
                              ' . ($this->element->getDescription() ? '<p>' . $this->element->getDescription() . '</p>' : '') . '                        
                              ' . ($this->element->getIcon() ? '  <div class="icon">
                                                        <i class="fas ' . $this->element->getIcon() . '"></i>
                                                    </div>' : '') . '
                              ' . ($this->element->getUrl() ? ' <a href="' . $this->element->getUrl() . '" class="small-box-footer">
                                                    ' . tr('More info') . ' <i class="fas fa-arrow-circle-right"></i>
                                                  </a>' : '') . '                        
                            </div>';

        return parent::render();
    }
}