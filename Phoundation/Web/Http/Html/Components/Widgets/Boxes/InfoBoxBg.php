<?php

namespace Phoundation\Web\Http\Html\Components\Widgets\Boxes;


/**
 * InfoBoxBg class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class InfoBoxBg extends Box
{
    /**
     * Renders and returns the HTML for this SmallBox object
     *
     * @inheritDoc
     */
    public function render(): ?string
    {
        $this->render = '   <div class="info-box bg-' . $this->mode . '">
                              <span class="info-box-icon"><i class="far ' . $this->icon . '"></i></span>
                
                              <div class="info-box-content">
                                <span class="info-box-text">' . $this->title . '</span>
                                <span class="info-box-number">' . $this->value . '</span>
                
                                ' . (($this->progress !== null) ? '   <div class="progress">
                                                                        <div class="progress-bar" style="width: ' . $this->progress . '%"></div>
                                                                      </div>' : '') . '
                                <span class="progress-description">
                                  ' . $this->description . '
                                </span>
                              </div>
                              <!-- /.info-box-content -->
                            </div>';

        return parent::render();
    }
}