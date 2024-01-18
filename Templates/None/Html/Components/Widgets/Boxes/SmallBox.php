<?php

declare(strict_types=1);


namespace Templates\None\Html\Components\Widgets\Boxes;

use Phoundation\Web\Html\Renderer;


/**
 * None Plugin SmallBox class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\None
 */
class SmallBox extends Renderer
{
    /**
     * SmallBox class constructor
     */
    public function __construct(\Phoundation\Web\Html\Components\Widgets\Boxes\SmallBox $element)
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
        $this->render = '   <div class="small-box bg-' . $this->render_object->getMode()->value . ($this->shadow ? ' ' . $this->shadow : '') . '">
                              <div class="inner">
                                <h3>' . $this->render_object->getValue() . '</h3>       
                                <p>' . $this->render_object->getTitle() . '</p>
                              </div>
                              ' . (($this->render_object->getProgress() !== null) ? '   <div class="progress">
                                                                      <div class="progress-bar" style="width: ' . $this->render_object->getProgress() . '%"></div>
                                                                    </div>' : '') . '
                              ' . ($this->render_object->getDescription() ? '<p>' . $this->render_object->getDescription() . '</p>' : '') . '                        
                              ' . ($this->render_object->getIcon() ? '  <div class="icon">
                                                        <i class="fas ' . $this->render_object->getIcon() . '"></i>
                                                    </div>' : '') . '
                              ' . ($this->render_object->getUrl() ? ' <a href="' . $this->render_object->getUrl() . '" class="small-box-footer">
                                                    ' . tr('More info') . ' <i class="fas fa-arrow-circle-right"></i>
                                                  </a>' : '') . '                        
                            </div>';

        return parent::render();
    }
}