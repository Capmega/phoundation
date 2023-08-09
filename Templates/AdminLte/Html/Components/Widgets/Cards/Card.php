<?php

declare(strict_types=1);


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
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
        $this->render = '   <div' . ($this->render_object->getId() ? ' id="' . $this->render_object->getId() . '"' : '') . ' class="card ' . ($this->render_object->getClass() ? $this->render_object->getClass() . ' ' : null) . ($this->render_object->getGradient() ? 'gradient-' . Html::safe($this->render_object->getGradient()) : '') . ($this->render_object->getMode()->value ? 'card-' . Html::safe($this->render_object->getMode()->value) : '') . ($this->render_object->getBackground() ? 'bg-' . Html::safe($this->render_object->getBackground()) : '') . '">';

        if ($this->render_object->getReloadSwitch() or $this->render_object->getMaximizeSwitch() or $this->render_object->getCollapseSwitch() or $this->render_object->getCloseSwitch() or $this->render_object->getTitle() or $this->render_object->getHeaderContent()) {
            $this->render .= '  <div class="card-header">
                                    <h3 class="card-title">' . $this->render_object->getTitle() . '</h3>
                                    <div class="card-tools">
                                      ' . $this->render_object->getHeaderContent() . '
                                      ' . ($this->render_object->getReloadSwitch() ? '   <button type="button" class="btn btn-tool" data-card-widget="card-refresh" data-source="widgets.html" data-source-selector="#card-refresh-content" data-load-on-init="false">
                                                                                           <i class="fas fa-sync-alt"></i>
                                                                                         </button>' : '') . '
                                      ' . ($this->render_object->getMaximizeSwitch() ? ' <button type="button" class="btn btn-tool" data-card-widget="maximize">
                                                                                           <i class="fas fa-expand"></i>
                                                                                         </button>' : '') . '
                                      ' . ($this->render_object->getCollapseSwitch() ? ' <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                                                           <i class="fas fa-' . ($this->render_object->getCollapsed() ? 'plus' : 'minus') . '"></i>
                                                                                         </button>' : '') . '
                                      ' . ($this->render_object->getCloseSwitch() ? '    <button type="button" class="btn btn-tool" data-card-widget="remove">
                                                                                           <i class="fas fa-times"></i>
                                                                                         </button>' : '') . '                              
                                    </div>
                                </div>';
        }

        $description = $this->render_object->getDescription();

        $this->render .= '      <!-- /.card-header -->
                                <div class="card-body">
                                    ' . ($description ? '<p class="card-description">' . $description . '</p>' : null) . '                                    
                                    ' . $this->render_object->getContent(). '
                                </div>';

        if ($this->render_object->getButtons()) {
            $this->render .= '  <div class="card-footer">
                                  ' . $this->render_object->getButtons()->render() . '           
                                </div>';
        }

        $this->render .= '  </div>';

        return parent::render();
    }
}