<?php

namespace Templates\AdminLte\Components\Widgets\Cards;



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
class Card extends \Phoundation\Web\Http\Html\Components\Widgets\Cards\Card
{
   /**
     * @inheritDoc
     */
    public function render(): ?string
    {
        $this->render = '   <div class="card bg-' . ($this->gradient ? 'gradient-' : '') . $this->mode . '">';

        if ($this->has_reload_button or $this->has_maximize_button or $this->has_collapse_button or $this->has_close_button or $this->title or $this->header_content) {
            $this->render .= '  <div class="card-header">
                                    <h3 class="card-title">' . $this->title . '</h3>
                                    <div class="card-tools">
                                      ' . $this->header_content . '
                                      ' . ($this->has_reload_button ? '   <button type="button" class="btn btn-tool" data-card-widget="card-refresh" data-source="widgets.html" data-source-selector="#card-refresh-content" data-load-on-init="false">
                                                                            <i class="fas fa-sync-alt"></i>
                                                                          </button>' : '') . '
                                      ' . ($this->has_maximize_button ? ' <button type="button" class="btn btn-tool" data-card-widget="maximize">
                                                                            <i class="fas fa-expand"></i>
                                                                          </button>' : '') . '
                                      ' . ($this->has_collapse_button ? ' <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                                            <i class="fas fa-minus"></i>
                                                                          </button>' : '') . '
                                      ' . ($this->has_close_button ? '    <button type="button" class="btn btn-tool" data-card-widget="remove">
                                                                            <i class="fas fa-times"></i>
                                                                          </button>' : '') . '                              
                                    </div>
                                </div>';
        }

        $this->render .= '    <!-- /.card-header -->
                      <div class="card-body">
                        ' . $this->content. '
                      </div>
                    </div>';

        return $this->render;
    }
}