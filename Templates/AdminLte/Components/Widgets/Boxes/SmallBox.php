<?php

namespace Templates\AdminLte\Components\Widgets\Boxes;



/**
 * AdminLte Plugin SmallBox class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class SmallBox extends \Phoundation\Web\Http\Html\Components\Widgets\Boxes\SmallBox
{
    /**
     * Renders and returns the HTML for this SmallBox object
     *
     * @inheritDoc
     */
    public function render(): ?string
    {
        $this->render = '   <div class="small-box bg-' . $this->mode . ($this->shadow ? ' ' . $this->shadow : '') . '">
                              <div class="inner">
                                <h3>' . $this->value . '</h3>       
                                <p>' . $this->title . '</p>
                              </div>
                              ' . (($this->progress !== null) ? '   <div class="progress">
                                                                      <div class="progress-bar" style="width: ' . $this->progress . '%"></div>
                                                                    </div>' : '') . '
                              ' . ($this->description ? '<p>' . $this->description . '</p>' : '') . '                        
                              ' . ($this->icon ? '  <div class="icon">
                                                        <i class="fas ' . $this->icon . '"></i>
                                                    </div>' : '') . '
                              ' . ($this->url ? ' <a href="' . $this->url . '" class="small-box-footer">
                                                    ' . tr('More info') . ' <i class="fas fa-arrow-circle-right"></i>
                                                  </a>' : '') . '                        
                            </div>';

        return $this->render;
    }
}