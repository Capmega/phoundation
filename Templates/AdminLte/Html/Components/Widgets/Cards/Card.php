<?php

namespace Templates\AdminLte\Html\Components\Widgets\Cards;

use Phoundation\Web\Http\Html\Html;
use Phoundation\Web\Http\Html\Renderer;


/**
 * AdminLte Plugin Card class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class Card extends Renderer
{
    /**
     * Card class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Components\Widgets\Cards\Card $element)
    {
        parent::__construct($element);
    }


    /**
     * @inheritDoc
     */
    public function render(): ?string
    {
        $this->render = '   <div class="card ' . ($this->element->getClass() ? $this->element->getClass() . ' ' : null) . ($this->element->getGradient() ? 'gradient-' . Html::safe($this->element->getGradient()) : '') . ($this->element->getMode()->value ? 'card-' . Html::safe($this->element->getMode()->value) : '') . ($this->element->getBackground() ? 'bg-' . Html::safe($this->element->getBackground()) : '') . '">';

        if ($this->element->hasReloadSwitch() or $this->element->hasMaximizeSwitch() or $this->element->hasCollapseSwitch() or $this->element->hasCloseSwitch() or $this->element->getTitle() or $this->element->getHeaderContent()) {
            $this->render .= '  <div class="card-header">
                                    <h3 class="card-title">' . Html::safe($this->element->getTitle()) . '</h3>
                                    <div class="card-tools">
                                      ' . $this->element->getHeaderContent() . '
                                      ' . ($this->element->hasReloadSwitch() ? '   <button type="button" class="btn btn-tool" data-card-widget="card-refresh" data-source="widgets.html" data-source-selector="#card-refresh-content" data-load-on-init="false">
                                                                                    <i class="fas fa-sync-alt"></i>
                                                                                  </button>' : '') . '
                                      ' . ($this->element->hasMaximizeSwitch() ? ' <button type="button" class="btn btn-tool" data-card-widget="maximize">
                                                                                    <i class="fas fa-expand"></i>
                                                                                  </button>' : '') . '
                                      ' . ($this->element->hasCollapseSwitch() ? ' <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                                                    <i class="fas fa-minus"></i>
                                                                                  </button>' : '') . '
                                      ' . ($this->element->hasCloseSwitch() ? '    <button type="button" class="btn btn-tool" data-card-widget="remove">
                                                                                    <i class="fas fa-times"></i>
                                                                                  </button>' : '') . '                              
                                    </div>
                                </div>';
        }

        $this->render .= '      <!-- /.card-header -->
                                <div class="card-body">
                                    ' . $this->element->getContent(). '
                                </div>';

        if ($this->element->getButtons()) {
            $this->render .= '  <div class="card-footer">
                                  ' . $this->element->getButtons()->render() . '           
                                </div>';
        }

        $this->render .= '  </div>';

        return parent::render();
    }
}